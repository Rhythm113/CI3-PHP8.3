# CI3 PHP 8.3 User Guide

A practical guide for setting up the database and building applications with this CodeIgniter 3 stack.

---

## Table of Contents

1. [Database Setup](#1-database-setup)
2. [Switching Databases](#2-switching-databases)
3. [Testing the Connection](#3-testing-the-connection)
4. [Sample Application: Task Manager API](#4-sample-application-task-manager-api)
5. [Using the JWT Auth System](#5-using-the-jwt-auth-system)
6. [Rate Limiting](#6-rate-limiting)
7. [Using the cURL Library](#7-using-the-curl-library)

---

## 1. Database Setup

### MySQL

```bash
mysql -u root -p
```

```sql
CREATE DATABASE ci3_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER 'ci3_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON ci3_db.* TO 'ci3_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Edit `application/config/database.php`:

```php
$active_group = 'mysql';

$db['mysql']['hostname'] = 'localhost';   // use 127.0.0.1 if socket errors occur
$db['mysql']['username'] = 'ci3_user';
$db['mysql']['password'] = 'your_password';
$db['mysql']['database'] = 'ci3_db';
```

> On Linux with Docker or GitHub Actions, use `127.0.0.1` as hostname instead of
> `localhost`. PHP's mysqli uses a Unix socket for `localhost` which may not exist
> in containerized environments.

### PostgreSQL

```bash
sudo -u postgres psql
```

```sql
CREATE DATABASE ci3_db;
CREATE USER ci3_user WITH PASSWORD 'your_password';
GRANT ALL PRIVILEGES ON DATABASE ci3_db TO ci3_user;
\q
```

Edit `application/config/database.php`:

```php
$active_group = 'postgre';

$db['postgre']['hostname'] = 'localhost';
$db['postgre']['username'] = 'ci3_user';
$db['postgre']['password'] = 'your_password';
$db['postgre']['database'] = 'ci3_db';
```

---

## 2. Switching Databases

Only the `$active_group` is connected on each request. To switch:

```php
// In application/config/database.php
$active_group = 'mysql';   // or 'postgre'
```

To load a second database inside a controller or model:

```php
$pg = $this->load->database('postgre', TRUE); // TRUE returns object
$result = $pg->get('users')->result();
```

---

## 3. Testing the Connection

Create a quick test controller at `application/controllers/Dbtest.php`:

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dbtest extends CI_Controller
{
    public function index()
    {
        if ($this->db->conn_id)
        {
            echo 'Connected to: ' . $this->db->database;
        }
        else
        {
            echo 'Connection failed.';
        }
    }

    public function postgre()
    {
        $pg = $this->load->database('postgre', TRUE);
        echo $pg->conn_id ? 'PostgreSQL OK' : 'PostgreSQL FAILED';
    }
}
```

Visit: `http://localhost/CI3/index.php/dbtest`

Remove this controller before going to production.

---

## 4. Sample Application: Task Manager API

This walks through building a simple Task Manager API using the existing API infrastructure.

### Step 1: Create the Database Table

#### MySQL

```sql
CREATE TABLE tasks (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(255)  NOT NULL,
    description TEXT,
    status      ENUM('pending', 'in_progress', 'done') DEFAULT 'pending',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### PostgreSQL

```sql
CREATE TABLE tasks (
    id          SERIAL PRIMARY KEY,
    title       VARCHAR(255)  NOT NULL,
    description TEXT,
    status      VARCHAR(20)   DEFAULT 'pending',
    created_at  TIMESTAMP     DEFAULT NOW(),
    updated_at  TIMESTAMP     DEFAULT NOW()
);
```

### Step 2: Create the Model

Create `application/models/Task_model.php`:

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Task_model extends CI_Model
{
    protected $table = 'tasks';

    public function get_all()
    {
        return $this->db->order_by('created_at', 'DESC')->get($this->table)->result();
    }

    public function get_by_id($id)
    {
        return $this->db->where('id', (int) $id)->get($this->table)->row();
    }

    public function create($data)
    {
        $this->db->insert($this->table, array(
            'title'       => $data['title'],
            'description' => isset($data['description']) ? $data['description'] : '',
            'status'      => isset($data['status'])      ? $data['status']      : 'pending'
        ));
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $allowed = array('title', 'description', 'status');
        $update  = array_intersect_key($data, array_flip($allowed));
        if (empty($update)) return FALSE;

        $this->db->where('id', (int) $id)->update($this->table, $update);
        return $this->db->affected_rows();
    }

    public function delete($id)
    {
        $this->db->where('id', (int) $id)->delete($this->table);
        return $this->db->affected_rows();
    }
}
```

### Step 3: Create the Controller

Create `application/controllers/Tasks.php`:

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! class_exists('API_Controller', FALSE))
{
    require_once APPPATH . 'core/API_Controller.php';
}

class Tasks extends API_Controller
{
    // All task routes require a valid JWT
    protected $require_auth = TRUE;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('task_model');
    }

    // GET /tasks
    public function index_get()
    {
        $tasks = $this->task_model->get_all();
        $this->success($tasks, 'Tasks retrieved');
    }

    // GET /tasks/view/1
    public function view_get($id = NULL)
    {
        if ( ! $id)
        {
            $this->error('Task ID required', 400, 'missing_id');
        }

        $task = $this->task_model->get_by_id($id);

        if ( ! $task)
        {
            $this->error('Task not found', 404, 'not_found');
        }

        $this->success($task, 'Task retrieved');
    }

    // POST /tasks/create
    public function create_post()
    {
        $data = $this->get_request_data();

        if (empty($data['title']))
        {
            $this->error('Title is required', 400, 'validation_error');
        }

        $id = $this->task_model->create($data);
        $this->success(array('id' => $id), 'Task created', 201);
    }

    // PUT /tasks/update/1
    public function update_put($id = NULL)
    {
        if ( ! $id)
        {
            $this->error('Task ID required', 400, 'missing_id');
        }

        $data = $this->get_request_data();
        $rows = $this->task_model->update($id, $data);

        if ($rows === FALSE)
        {
            $this->error('Nothing to update', 400, 'no_fields');
        }

        $this->success(array('updated' => $rows), 'Task updated');
    }

    // DELETE /tasks/delete/1
    public function delete_delete($id = NULL)
    {
        if ( ! $id)
        {
            $this->error('Task ID required', 400, 'missing_id');
        }

        $rows = $this->task_model->delete($id);

        if ( ! $rows)
        {
            $this->error('Task not found', 404, 'not_found');
        }

        $this->success(array('deleted' => $rows), 'Task deleted');
    }

    // Route: method_verb pattern
    public function _remap($method, $params = array())
    {
        $verb     = strtolower($this->input->method());
        $callable = $method . '_' . $verb;

        if (method_exists($this, $callable))
        {
            return call_user_func_array(array($this, $callable), $params);
        }

        if (method_exists($this, $method))
        {
            return call_user_func_array(array($this, $method), $params);
        }

        $this->error('Endpoint not found', 404, 'not_found');
    }
}
```

### Step 4: Add Routes

In `application/config/routes.php`:

```php
$route['tasks']              = 'tasks/index';
$route['tasks/(:any)']       = 'tasks/$1';
```

### Step 5: Test the Endpoints

Get a token first:

```bash
curl -s -X POST http://localhost/CI3/index.php/api/token \
  -H "Content-Type: application/json" \
  -d '{"username":"testuser","password":"testpass"}'
```

Copy the `access_token` from the response and use it below:

```bash
TOKEN="paste_your_token_here"

# List all tasks
curl -s http://localhost/CI3/index.php/tasks \
  -H "Authorization: Bearer $TOKEN"

# Create a task
curl -s -X POST http://localhost/CI3/index.php/tasks/create \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"title":"Buy groceries","description":"Milk, eggs, bread","status":"pending"}'

# Get task by ID
curl -s http://localhost/CI3/index.php/tasks/view/1 \
  -H "Authorization: Bearer $TOKEN"

# Update a task
curl -s -X PUT http://localhost/CI3/index.php/tasks/update/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"status":"done"}'

# Delete a task
curl -s -X DELETE http://localhost/CI3/index.php/tasks/delete/1 \
  -H "Authorization: Bearer $TOKEN"
```

Or use the Python test script:

```bash
python scripts/test_api.py http://localhost/CI3/index.php
```

---

## 5. Using the JWT Auth System

### Generating Tokens

```php
// In any controller extending API_Controller
$token = $this->jwt_lib->generate_token(array(
    'user_id'  => $user->id,
    'username' => $user->username,
    'role'     => $user->role
));

$refresh = $this->jwt_lib->generate_refresh_token(array(
    'user_id' => $user->id
));
```

### Validating Tokens

```php
$decoded = $this->jwt_lib->validate_token($token_string);
if ($decoded)
{
    $user_id = $decoded->data->user_id;
}
```

### Protecting an Endpoint

Set `$require_auth = TRUE` on the controller class to protect all routes:

```php
class MyController extends API_Controller
{
    protected $require_auth = TRUE;
}
```

Or protect individual methods manually:

```php
public function secret_get()
{
    $this->_authenticate();
    $user = $this->get_auth_user();
    // $user->data->user_id is available here
}
```

### JWT Configuration

Edit `application/config/jwt.php`:

```php
$config['jwt_secret']             = 'replace-with-a-random-64-char-string';
$config['jwt_algorithm']          = 'HS256';
$config['jwt_expiration']         = 3600;        // 1 hour
$config['jwt_refresh_expiration'] = 604800;      // 7 days
$config['jwt_issuer']             = 'ci3-api';
$config['jwt_audience']           = '';
```

Generate a secure secret on Linux:

```bash
openssl rand -base64 64
```

---

## 6. Rate Limiting

Rate limiting is on by default for all controllers that extend `API_Controller`.

### Configuration

Edit `application/config/rate_limit.php`:

```php
$config['rate_limit_requests'] = 60;     // max requests per window
$config['rate_limit_window']   = 60;     // window in seconds
$config['rate_limit_driver']   = 'file'; // 'file' or 'redis'
```

### Response Headers

Every API response includes:

| Header                  | Description                         |
|-------------------------|-------------------------------------|
| X-RateLimit-Limit       | Maximum requests allowed per window |
| X-RateLimit-Remaining   | Requests remaining in this window   |
| X-RateLimit-Reset       | Unix timestamp when window resets   |

When the limit is exceeded, the API returns:

```json
{
    "status": 429,
    "error": "rate_limit_exceeded",
    "message": "Too Many Requests"
}
```

### Disabling Rate Limiting

To disable for a specific controller:

```php
class PublicController extends API_Controller
{
    protected $rate_limit = FALSE;
}
```

### Excluding Routes

In `rate_limit.php`:

```php
$config['rate_limit_exclude'] = array(
    'api/health',
    'api/status'
);
```

### Using Redis

```php
$config['rate_limit_driver'] = 'redis';
```

Make sure Redis is running and `application/config/redis.php` is configured.

---

## 7. Using the cURL Library

The `Curl_lib` library is loaded automatically in all API controllers.

### GET Request

```php
$response = $this->curl_lib->get('https://api.example.com/users');
$data     = json_decode($response, TRUE);
```

### POST JSON

```php
$response = $this->curl_lib->post_json(
    'https://api.example.com/users',
    array('name' => 'Alice', 'email' => 'alice@example.com')
);
```

### With Auth Header

```php
$this->curl_lib->set_header('Authorization', 'Bearer ' . $token);
$response = $this->curl_lib->get('https://api.example.com/me');
```

### In a Non-API Controller

Load the library manually:

```php
$this->load->library('curl_lib');
$response = $this->curl_lib->get('https://example.com/api/data');
```

---

## File Reference

| File                                    | Purpose                              |
|-----------------------------------------|--------------------------------------|
| application/config/database.php         | MySQL and PostgreSQL settings        |
| application/config/jwt.php              | JWT secret, algorithm, expiry        |
| application/config/rate_limit.php       | Rate limit thresholds and driver     |
| application/config/redis.php            | Redis connection settings            |
| application/core/API_Controller.php     | Base controller for all API routes   |
| application/libraries/Jwt_lib.php       | JWT encode/decode wrapper            |
| application/libraries/Rate_limiter.php  | IP-based rate limiting               |
| application/libraries/Curl_lib.php      | HTTP client wrapper                  |
| scripts/test_api.py                     | Python API test script               |
| scripts/test_api.sh                     | Bash API test script                 |
| SETUP_GUIDE.md                          | Linux server setup instructions      |
