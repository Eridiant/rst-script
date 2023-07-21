<?php
require 'vendor/autoload.php'; // Load the Dotenv library

use Dotenv\Dotenv;
use \AmoCRM\Client;

class SendAmo
{
    private $conn;
    private $amo;

    public function __construct()
    {
        // Load environment variables from .env file
        $dotenv = Dotenv::createImmutable(__DIR__);
        $dotenv->load();

        // Database credentials
        $dbHost = $_ENV['DB_HOST'];
        $dbUsername = $_ENV['DB_USERNAME'];
        $dbPassword = $_ENV['DB_PASSWORD'];
        $dbName = $_ENV['DB_NAME'];

        $subdomain = $_ENV['AMO_SUBDOMAIN'];
        $login = $_ENV['AMO_LOGIN'];
        $apikey = $_ENV['AMO_APIKEY'];


        $this->amo = new Client($subdomain, $login, $apikey);

        // Connect to the database
        $this->conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

        // Check connection
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public function saveIdToDatabase($id)
    {
        // Sanitize the ID input (to prevent SQL injection)
        $sanitizedId = $this->conn->real_escape_string($id);

        // Prepare and execute the SQL query to insert the ID into the database
        $sql = "INSERT INTO rst_key (id) VALUES ('$sanitizedId')";
        if ($this->conn->query($sql) === TRUE) {
            echo "ID saved successfully!";
        } else {
            echo "Error: " . $sql . "<br>" . $this->conn->error;
        }
    }

    public function sendApplication()
    {
        try {
            // $key = Key::find()->where(['id' => 1])->one();
            // // Создание клиента
            // $subdomain = $key->key;            // Поддомен в амо срм
            // $login     = $key->value;            // Логин в амо срм
            // $apikey    = $key->content;            // api ключ


            // create lead
            $lead = $this->amo->lead;
            $lead['name'] = 'ГуглГрузия';
            $lead['responsible_user_id'] = 5847651; // ID ответсвенного 
            $lead['pipeline_id'] = 5581734; // ID воронки
            $lead['status_id'] = 49943004; // for check

            $lead['tags'] = ['ГуглГрузия'];

            // $lead->addCustomField(809203, $request->post('name'));

            // $lead->addCustomField(319701, $request->post('phone'));

            $lead->addCustomField(815608, $request->post('message'));

            // $lead->addCustomField(319703, 'test@test.com');

            $lead->addCustomField(673225, 'ddageorgia.com');

            // $lead->addCustomField(799655, 'ip');

            $message->status_amo_id = $lead->apiAdd();

            $contact = $this->amo->contact;
            $contact['name'] = $request->post('name');
            $contact->addCustomField(171145, [
                [$request->post('phone'), 'WORK'],
            ]);
            $message->status_contact_amo_id = $contact->apiAdd();

            $link = $this->amo->links;
            $link['from'] = 'leads';
            $link['from_id'] = $message->status_amo_id;
            $link['to'] = 'contacts';
            $link['to_id'] = $message->status_contact_amo_id;

            $message->status_link_amo_id = json_decode($link->apiLink(), true);
        } catch (\Throwable $th) {
            // throw $th;
            // var_dump($th);
        }
    }

    public function closeConnection()
    {
        // Close the database connection
        $this->conn->close();
    }
}


// Function to update the rst_message table with new values
function updateRstMessageTable($statusAmoId, $statusContactAmoId, $statusLinkAmoId) {
    $host = 'your_database_host';
    $db   = 'your_database_name';
    $user = 'your_database_username';
    $pass = 'your_database_password';

    // Create a new MySQLi instance
    $mysqli = new mysqli($host, $user, $pass, $db);

    // Check if the connection was successful
    if ($mysqli->connect_error) {
        return "Connection failed: " . $mysqli->connect_error;
    }

    // Prepare the SQL query
    $sql = "UPDATE rst_message SET status_amo_id = ?, status_contact_amo_id = ?, status_link_amo_id = ?";

    // Prepare the statement
    $stmt = $mysqli->prepare($sql);

    // Bind parameters and execute the query
    $stmt->bind_param("iii", $statusAmoId, $statusContactAmoId, $statusLinkAmoId);

    // Execute the update query
    if ($stmt->execute()) {
        $rowCount = $stmt->affected_rows;
        if ($rowCount > 0) {
            $stmt->close();
            $mysqli->close();
            return "Update successful. $rowCount row(s) updated.";
        } else {
            $stmt->close();
            $mysqli->close();
            return "No rows were updated.";
        }
    } else {
        $stmt->close();
        $mysqli->close();
        return "Error executing the query: " . $mysqli->error;
    }
}

// Check if the ID parameter is provided in the API call
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    saveIdToDatabase($id);
} else {
    echo "Error: ID parameter not provided.";
}
