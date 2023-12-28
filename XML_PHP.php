<?php

class XmlProcessor
{
    private $conn;

    public function __construct($servername, $username, $password, $dbname)
    {
        $this->conn = new mysqli($servername, $username, $password, $dbname);

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public function processXmlFile($xmlFile)
    {
        $xml = simplexml_load_file($xmlFile);

        foreach ($xml->record as $record) {
            $loRange = (string) $record->LO_RANGE;
            $hiRange = (string) $record->HI_RANGE;
            $bilcurrency_codeP = $record->BILCURRENCY_CODE;
            $bankName = $record->BANK_NAME;

            $loRangePrefix = substr($loRange, 0, 6);
            $hiRangePrefix = substr($hiRange, 0, 6);

            if ($loRangePrefix == $hiRangePrefix) {
                $Range = $loRangePrefix;
            }

            $searchBilcurrency_code = [];

            $xml2 = simplexml_load_file($xmlFile);

            foreach ($xml2->record as $record2) {
                foreach ($record2 as $elementName2 => $elementValue2) 
                {
                    if (($elementName2 == 'LO_RANGE') && ((substr($elementValue2, 0, 6)) == $loRangePrefix)) 
                    {
                        foreach ($record2 as $elementName2 => $elementValue2) 
                        {
                            if ((trim($bilcurrency_codeP) == trim($elementValue2)) && ('BILCURRENCY_CODE' == $elementName2)) 
                            {
                                foreach ($record2 as $elementName2 => $elementValue2) 
                                {
                                    if (($elementName2 == 'BANK_NAME') && (trim($elementValue2) != trim($bankName))) 
                                    {
                                        $searchBilcurrency_code[] = $bilcurrency_codeP;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            if (!empty($searchBilcurrency_code)) {
                $bankName = $bankName . " DIFFERENT";
            }

            $this->insertOrUpdateDatabase($bankName, $bilcurrency_codeP, $Range);
        }
    }

    private function insertOrUpdateDatabase($bankName, $bilcurrency_codeP, $Range)
    {
        $sql = "INSERT INTO bins (name, code, bin) 
                VALUES ('$bankName', $bilcurrency_codeP, $Range) 
                ON DUPLICATE KEY UPDATE name='$bankName', code=$bilcurrency_codeP, bin=$Range;";

        if ($this->conn->query($sql) === TRUE) {
            echo $bankName. ' '. $bilcurrency_codeP .' '. $Range ." Данные name успешно внесены в базу данных.\n";
        }
         else {
            echo "Ошибка при выполнении запроса: " . $this->conn->error . "\n";
        }
    }

    public function closeConnection()
    {
        $this->conn->close();
    }
}

// Использование класса
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pskb";

$xmlFile = 'some_bins.xml';

$xmlProcessor = new XmlProcessor($servername, $username, $password, $dbname);
$xmlProcessor->processXmlFile($xmlFile);
$xmlProcessor->closeConnection();

?>
