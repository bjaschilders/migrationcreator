<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Migration Generator</title>
    <style>
        .dynamic-field { margin-bottom: 15px; }
    </style>
    <script>
        let fieldCount = 0;

        function addField() {
            fieldCount++;
            const container = document.getElementById('dynamic-fields');

            const fieldDiv = document.createElement('div');
            fieldDiv.classList.add('dynamic-field');
            fieldDiv.id = `field-${fieldCount}`;

            fieldDiv.innerHTML = `
                <label for="title-${fieldCount}">Column Title:</label>
                <input type="text" name="column_title_${fieldCount}" id="title-${fieldCount}" required>
                <label for="type-${fieldCount}">Type:</label>
                <select name="column_type_${fieldCount}" id="type-${fieldCount}" onchange="handleTypeChange(${fieldCount})">
                    <option value="string">String</option>
                    <option value="int">Int</option>
                    <option value="timestamp">Timestamp</option>
                    <option value="foreign">Foreign</option>
                </select>
                <div id="foreign-fields-${fieldCount}" style="display: none; margin-top: 10px;">
                    <label for="referenced-id-${fieldCount}">Referenced ID:</label>
                    <input type="text" name="referenced_id_${fieldCount}" id="referenced-id-${fieldCount}">
                    <label for="referenced-table-${fieldCount}">Referenced Table:</label>
                    <input type="text" name="referenced_table_${fieldCount}" id="referenced-table-${fieldCount}">
                </div>
            `;

            container.appendChild(fieldDiv);
        }

        function handleTypeChange(index) {
            const typeSelect = document.getElementById(`type-${index}`);
            const foreignFields = document.getElementById(`foreign-fields-${index}`);

            if (typeSelect.value === 'foreign') {
                foreignFields.style.display = 'block';
            } else {
                foreignFields.style.display = 'none';
            }
        }
    </script>
</head>
<body>
    <h1>Generate PHP Migration File</h1>
    <form action="" method="POST">
        <label for="title">Migration Title:</label>
        <input type="text" id="title" name="title" required>
        <br><br>
        <label for="tabletitle">Table Name:</label>
        <input type="text" id="tabletitle" name="tabletitle" required>
        <br><br>

        <h3>Define Table Columns</h3>
        <div id="dynamic-fields"></div>
        <button type="button" onclick="addField()">+ Add Column</button>
        <br><br>
        <button type="submit">Generate File</button>
    </form>

    <?php
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $title = trim($_POST["title"]);
        $tableTitle = trim($_POST["tabletitle"]);
        $title = strtolower(str_replace(' ', '_', $title));
        $dateTime = date('Y_m_d_His');
        $fileName = $dateTime . '_' . $title . '.php';
        $filePath = __DIR__ . '/' . $fileName;

        $columns = '';
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'column_title_') === 0) {
                $index = str_replace('column_title_', '', $key);
                $columnTitle = $value;
                $columnType = $_POST["column_type_$index"];

                switch ($columnType) {
                    case 'string':
                        $columns .= "\t\t\t\$table->string('$columnTitle');\n";
                        break;
                    case 'int':
                        $columns .= "\t\t\t\$table->integer('$columnTitle');\n";
                        break;
                    case 'timestamp':
                        $columns .= "\t\t\t\$table->timestamp('$columnTitle', 0)->nullable();\n";
                        break;
                    case 'foreign':
                        $referencedId = $_POST["referenced_id_$index"];
                        $referencedTable = $_POST["referenced_table_$index"];
                        $columns .= "\t\t\t\$table->foreign('$columnTitle')->references('$referencedId')->on('$referencedTable')->onDelete('cascade');\n";
                        break;
                }
            }
        }

        $fileContent = <<<EOT
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('$tableTitle', function (Blueprint \$table) {
            \$table->id();
$columns\t\t\t\$table->timestamps();
\t\t});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('$tableTitle');
    }
};
EOT;

        if (file_put_contents($filePath, $fileContent) !== false) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=1&filename=" . urlencode($fileName));
            exit();
        } else {
            echo "<p>Error: Unable to create the file.</p>";
        }
    }

    if (isset($_GET['success']) && $_GET['success'] == 1 && isset($_GET['filename'])) {
        $fileName = htmlspecialchars($_GET['filename']);
        echo "<p>File <strong>$fileName</strong> created successfully.</p>";
    }
    ?>
</body>
</html>
