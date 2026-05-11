<?php

/**
 * Example: CodeIgniter 3 Integration
 * 
 * 1. Install via composer: composer require darkauth/darkauth
 * 2. In application/config/config.php set $config['composer_autoload'] = TRUE;
 * 3. Create application/config/darkauth.php (see below)
 * 4. Load and use in your controller.
 */

// --- application/config/darkauth.php ---
/*
$config = [
    'defaults' => [
        'guard' => 'web',
    ],
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'api' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ],
    ],
    'providers' => [
        'users' => [
            'driver' => 'database',
            'table' => 'users',
            'callback' => function($id) {
                // In CI3, you might do:
                // $CI =& get_instance();
                // return $CI->db->get_where('users', ['id' => $id])->row();
                
                // For demonstration:
                return new \Darkauth\Models\GenericUser([
                    'id' => $id,
                    'username' => 'admin',
                    'email' => 'admin@example.com'
                ]);
            }
        ],
    ],
    'jwt' => [
        'secret' => 'SECRET_KEY_HERE',
        'algo' => 'HS256',
    ]
];
*/

// --- application/controllers/Auth.php ---

class Auth extends CI_Controller 
{
    public function __construct()
    {
        parent::__construct();
        
        // Load the library (maps to Darkauth\Support\CI3Auth)
        // You can use an alias in your autoloader or manually
        $this->load->library(\Darkauth\Support\CI3Auth::class, null, 'darkauth');
    }

    public function login()
    {
        // Simple login example
        $user = new \Darkauth\Models\GenericUser(['id' => 1, 'username' => 'john_doe']);
        
        $this->darkauth->login($user);
        
        echo "Logged in as: " . $this->darkauth->user()->username;
    }

    public function check_auth()
    {
        if ($this->darkauth->check()) {
            echo "Welcome back, " . $this->darkauth->user()->username;
        } else {
            echo "Please log in.";
        }
    }

    public function get_api_token()
    {
        if ($this->darkauth->check()) {
            $token = $this->darkauth->guard('api')->issueToken($this->darkauth->user());
            echo json_encode(['token' => $token]);
        }
    }

    public function logout()
    {
        $this->darkauth->logout();
        echo "Logged out.";
    }
}
