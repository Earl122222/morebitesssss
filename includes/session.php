<?php
session_start();

class Session {
    private $user_id;
    private $user_type;
    private $branch_id;
    private $is_logged_in = false;

    public function __construct() {
        $this->check_login();
    }

    private function check_login() {
        if (isset($_SESSION['user_id'])) {
            $this->user_id = $_SESSION['user_id'];
            $this->user_type = $_SESSION['user_type'];
            $this->branch_id = $_SESSION['branch_id'] ?? null;
            $this->is_logged_in = true;
        }
    }

    public function set_user_data($data) {
        $_SESSION['user_id'] = $data['user_id'];
        $_SESSION['user_type'] = $data['user_type'];
        $_SESSION['branch_id'] = $data['branch_id'] ?? null;
        $_SESSION['username'] = $data['username'];
        
        $this->user_id = $data['user_id'];
        $this->user_type = $data['user_type'];
        $this->branch_id = $data['branch_id'] ?? null;
        $this->is_logged_in = true;
    }

    public function logout() {
        unset($_SESSION['user_id']);
        unset($_SESSION['user_type']);
        unset($_SESSION['branch_id']);
        unset($_SESSION['username']);
        
        $this->user_id = null;
        $this->user_type = null;
        $this->branch_id = null;
        $this->is_logged_in = false;
        
        session_destroy();
    }

    public function is_logged_in() {
        return $this->is_logged_in;
    }

    public function get_user_id() {
        return $this->user_id;
    }

    public function get_user_type() {
        return $this->user_type;
    }

    public function get_branch_id() {
        return $this->branch_id;
    }

    public function is_admin() {
        return strtolower($this->user_type) === 'admin';
    }

    public function is_staff() {
        return strtolower($this->user_type) === 'staff';
    }

    public function is_manager() {
        return $this->user_type === 'manager';
    }

    public function is_cashier() {
        return $this->user_type === 'cashier';
    }

    // Function to filter records based on user role and branch
    public function filter_query($table) {
        if ($this->is_admin()) {
            return "SELECT * FROM $table"; // Admins can see all records
        } else if ($this->is_manager() && $this->branch_id) {
            return "SELECT * FROM $table WHERE branch_id = " . intval($this->branch_id);
        } else if ($this->is_cashier() && $this->branch_id) {
            return "SELECT * FROM $table WHERE branch_id = " . intval($this->branch_id) . 
                   " AND user_id = " . intval($this->user_id);
        }
        return false; // Return false if no valid filter could be applied
    }
}

// Initialize the session
$session = new Session();

// Function to check if user has access to a specific record
function has_access_to_record($record_branch_id, $record_user_id = null) {
    global $session;
    
    if ($session->is_admin()) {
        return true; // Admins have access to all records
    }
    
    if ($session->is_manager()) {
        return $record_branch_id === $session->get_branch_id();
    }
    
    if ($session->is_cashier()) {
        return $record_branch_id === $session->get_branch_id() && 
               $record_user_id === $session->get_user_id();
    }
    
    return false;
}

// Function to get current user's accessible records
function get_accessible_records($table) {
    global $session;
    
    $query = $session->filter_query($table);
    if (!$query) {
        return [];
    }
    
    // Execute query here using your database connection
    return execute_query($query);
} 