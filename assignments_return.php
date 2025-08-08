<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assignment_id = (int)$_POST['assignment_id'];
    $return_condition = $conn->real_escape_string($_POST['return_condition']);
    $return_location_id = (int)$_POST['return_location_id'];
    $return_notes = $conn->real_escape_string($_POST['return_notes']);
    
    // Get assignment details
    $query = "SELECT * FROM assignments WHERE assignment_id = $assignment_id";
    $result = $conn->query($query);
    
    if ($result->num_rows === 1) {
        $assignment = $result->fetch_assoc();
        $item_id = $assignment['item_id'];
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // 1. Update assignment with return details
            $conn->query("UPDATE assignments SET 
                         return_date = ".date('Y-m-d').",
                         return_condition = '$return_condition',
                         return_notes = " . ($return_notes ? "'$return_notes'" : "NULL") . ",
                         updated_at = ".date('Y-m-d H:i:s')."
                         WHERE assignment_id = $assignment_id");
            
            // 2. Update item status and location
            $new_status = $return_condition;
            $conn->query("UPDATE items SET 
                         status = '$new_status',
                         location_id = $return_location_id
                         WHERE item_id = $item_id");
            
            // Get item data
            $item_data = "SELECT asset_id,name FROM items WHERE item_id = '$item_id'";
            $item_data_result = $conn->query($item_data);
            $item_data_result_row = mysqli_fetch_array($item_data_result);

            // 3. Log the action
            $return_condition_words ="";
            switch ($return_condition) {
                case "1":
                  $return_condition_words = "Good (Working Condition)";
                  break;
                case "5":
                    $return_condition_words = "Damaged (Needs Repair)";
                  break;
                case "6":
                    $return_condition_words = "Lost/Missing";
                  break;
                default:
                    $return_condition_words = "Not selected";
              }

            $conn->query("INSERT INTO audit_log (
                user_id, action, table_affected, record_id, action_details, ip_address,created_at
            ) VALUES (
                '{$_SESSION['user_id']}', 'Return', 'assignments', '$assignment_id', 
                'Returned asset ID: ".$item_data_result_row["asset_id"]." with condition: $return_condition_words', 
                '{$_SERVER['REMOTE_ADDR']}','".date('Y-m-d H:i:s')."'
            )");
            $audit_log_id = $conn->insert_id;
            
            // Get assigned user data
            $user_data = "SELECT employee_id, employee_name FROM employee WHERE id = '".$assignment['user_id']."'";
            $user_data_result = $conn->query($user_data);
            $user_data_result_row = mysqli_fetch_array($user_data_result);
            
            // Item history tracking
            
            $description = "Asset ID - ".$item_data_result_row["asset_id"]." (".$item_data_result_row["name"].") has been returned to IT Department by the user ".$user_data_result_row["employee_id"]."-".$user_data_result_row["employee_name"].". | by ".$_SESSION['full_name'];
            $conn->query("INSERT INTO item_history (`item_id`, `user_id`, `action`, `description`,`audit_log_id`,`created_at`) VALUES (
                '$item_id', '{$_SESSION['user_id']}', 'Asset returned', '$description','$audit_log_id','".date('Y-m-d H:i:s')."'
            )");
            
            // Commit transaction
            $conn->commit();
            
            $_SESSION['message'] = "Equipment returned successfully. Status updated to: $return_condition_words";
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $_SESSION['error'] = "Error processing return: " . $e->getMessage();
        }
    }
}

header("Location: assignments.php");
exit();
?>