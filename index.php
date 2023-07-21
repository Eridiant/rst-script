<?php
require 'vendor/autoload.php'; // Load the Dotenv library

use Dotenv\Dotenv;
use \AmoCRM\Client;

class SendAmo
{
    private $conn;
    private $amo;
    private $tbmessages;

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

        $this->tbmessages = $_ENV['TB_MESSAGES'];

        sleep(5);

        $this->amo = new Client($subdomain, $login, $apikey);

        // Connect to the database
        $this->conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
        $this->conn->set_charset("utf8mb4");

        // Check connection
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public function sendApplication()
    {
        $timeLimit = strtotime('-3 days');

        // $sql = "SELECT * FROM {$this->tbmessages} WHERE status_link_amo_id = 0 AND created_at > {$timeLimit} LIMIT 5";
        $sql = "SELECT * FROM {$this->tbmessages} WHERE country IS NULL AND LIMIT 5";
        $result = $this->conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {



                // Get the ID of the row
                $id = $row['id'];

                // Update the row with new values
                $updateSql = "UPDATE {$this->tbmessages} 
                            SET country = 'adsf '
                            WHERE id = $id";

                if ($this->conn->query($updateSql) === TRUE) {
                    echo "Row with ID $id updated successfully.\n";
                } else {
                    echo "Error updating row with ID $id: " . $this->conn->error . "\n";
                }
                continue;
                // create lead
                if (empty($row['status_amo_id'])) {
                    try {
                        $lead = $this->amo->lead;
                        $lead['name'] = 'ГуглГрузия';
                        $lead['responsible_user_id'] = 5847651; // ID ответсвенного 
                        $lead['pipeline_id'] = 5581734; // ID воронки
                        $lead['status_id'] = 49943004; // for check

                        $lead['tags'] = ['ГуглГрузия'];
                        $lead->addCustomField(815608, $row['message']);

                        // $lead->addCustomField(319703, 'test@test.com');

                        $lead->addCustomField(673225, 'ddageorgia.com');

                        // $lead->addCustomField(799655, 'ip');

                        $lead_amo_id = (int)$lead->apiAdd();
                    } catch (\Throwable $th) {
                        //throw $th;
                        continue;
                    }
                } else {
                    $lead_amo_id = $row['status_amo_id'];
                }

                // create contact
                if (empty($row['status_contact_amo_id'])) {
                    try {
                        $contact = $this->amo->contact;
                        $contact['name'] = $row['name'];
                        $contact->addCustomField(171145, [
                            [$row['phone'], 'WORK'],
                        ]);
                        $contact_amo_id = $contact->apiAdd();
            
                    } catch (\Throwable $th) {
                        // throw $th;
                        // var_dump($th);
                        continue;
                    }
                } else {
                    $contact_amo_id = $row['status_contact_amo_id'];
                }

                // create link
                if (empty($row['status_link_amo_id'])) {
                    try {
                        $link = $this->amo->links;
                        $link['from'] = 'leads';
                        $link['from_id'] = $lead_amo_id;
                        $link['to'] = 'contacts';
                        $link['to_id'] = $contact_amo_id;
            
                        $link_amo_id = json_decode($link->apiLink(), true);
                    } catch (\Throwable $th) {
                        //throw $th;
                        continue;
                    }
                }

                // Get the ID of the row
                $id = $row['id'];

                // Update the row with new values
                $updateSql = "UPDATE {$this->tbmessages} 
                            SET status_amo_id = $lead_amo_id,
                                status_contact_amo_id = $contact_amo_id,
                                status_link_amo_id = $link_amo_id
                            WHERE id = $id";

                if ($this->conn->query($updateSql) === TRUE) {
                    echo "Row with ID $id updated successfully.\n";
                } else {
                    echo "Error updating row with ID $id: " . $this->conn->error . "\n";
                }
            }
        }
    }

    public function closeConnection()
    {
        // Close the database connection
        $this->conn->close();
    }
}


$db = new SendAmo();

$db->sendApplication();

$db->closeConnection();