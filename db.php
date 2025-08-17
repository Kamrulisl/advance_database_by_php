<?php
// Database configuration
$host = 'localhost';
$dbname = 'new';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'update_cell':
            $table = $_POST['table'];
            $column = $_POST['column'];
            $value = $_POST['value'];
            $id = $_POST['id'];
            $primaryKey = $_POST['primary_key'];
            
            try {
                $stmt = $pdo->prepare("UPDATE `$table` SET `$column` = ? WHERE `$primaryKey` = ?");
                $stmt->execute([$value, $id]);
                echo json_encode(['success' => true, 'message' => 'Cell updated successfully']);
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'delete_row':
            $table = $_POST['table'];
            $id = $_POST['id'];
            $primaryKey = $_POST['primary_key'];
            
            try {
                $stmt = $pdo->prepare("DELETE FROM `$table` WHERE `$primaryKey` = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'Row deleted successfully']);
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'insert_row':
            $table = $_POST['table'];
            $data = $_POST['data'];
            
            try {
                $columns = array_keys($data);
                $columnList = '`' . implode('`, `', $columns) . '`';
                $placeholders = str_repeat('?,', count($columns) - 1) . '?';
                
                $stmt = $pdo->prepare("INSERT INTO `$table` ($columnList) VALUES ($placeholders)");
                $stmt->execute(array_values($data));
                
                echo json_encode(['success' => true, 'message' => 'Row inserted successfully', 'id' => $pdo->lastInsertId()]);
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;

        case 'create_table':
            $tableName = $_POST['table_name'];
            $columns = $_POST['columns'];
            
            try {
                $sql = "CREATE TABLE `$tableName` (";
                $columnDefs = [];
                
                foreach ($columns as $col) {
                    $colDef = "`{$col['name']}` {$col['type']}";
                    if ($col['length']) $colDef .= "({$col['length']})";
                    if ($col['null'] === 'NO') $colDef .= ' NOT NULL';
                    if ($col['default']) $colDef .= " DEFAULT '{$col['default']}'";
                    if ($col['auto_increment']) $colDef .= ' AUTO_INCREMENT';
                    if ($col['primary']) $colDef .= ' PRIMARY KEY';
                    $columnDefs[] = $colDef;
                }
                
                $sql .= implode(', ', $columnDefs) . ')';
                $pdo->exec($sql);
                
                echo json_encode(['success' => true, 'message' => 'Table created successfully']);
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;

        case 'drop_table':
            $tableName = $_POST['table_name'];
            
            try {
                $stmt = $pdo->prepare("DROP TABLE `$tableName`");
                $stmt->execute();
                echo json_encode(['success' => true, 'message' => 'Table dropped successfully']);
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;

        case 'truncate_table':
            $tableName = $_POST['table_name'];
            
            try {
                $stmt = $pdo->prepare("TRUNCATE TABLE `$tableName`");
                $stmt->execute();
                echo json_encode(['success' => true, 'message' => 'Table truncated successfully']);
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;

        case 'add_column':
            $tableName = $_POST['table_name'];
            $columnName = $_POST['column_name'];
            $columnType = $_POST['column_type'];
            $columnLength = $_POST['column_length'];
            $columnNull = $_POST['column_null'];
            $columnDefault = $_POST['column_default'];
            
            try {
                $sql = "ALTER TABLE `$tableName` ADD `$columnName` $columnType";
                if ($columnLength) $sql .= "($columnLength)";
                if ($columnNull === 'NO') $sql .= ' NOT NULL';
                if ($columnDefault) $sql .= " DEFAULT '$columnDefault'";
                
                $pdo->exec($sql);
                echo json_encode(['success' => true, 'message' => 'Column added successfully']);
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;

        case 'drop_column':
            $tableName = $_POST['table_name'];
            $columnName = $_POST['column_name'];
            
            try {
                $sql = "ALTER TABLE `$tableName` DROP COLUMN `$columnName`";
                $pdo->exec($sql);
                echo json_encode(['success' => true, 'message' => 'Column dropped successfully']);
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;

        case 'execute_query':
            $query = $_POST['query'];
            
            try {
                $stmt = $pdo->prepare($query);
                $stmt->execute();
                
                if (stripos($query, 'SELECT') === 0) {
                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode(['success' => true, 'data' => $result, 'message' => 'Query executed successfully']);
                } else {
                    $affected = $stmt->rowCount();
                    echo json_encode(['success' => true, 'affected_rows' => $affected, 'message' => 'Query executed successfully']);
                }
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;

        case 'search_table':
            $tableName = $_POST['table_name'];
            $searchTerm = $_POST['search_term'];
            $searchColumn = $_POST['search_column'] ?? '';
            
            try {
                if ($searchColumn && $searchColumn !== 'all') {
                    $sql = "SELECT * FROM `$tableName` WHERE `$searchColumn` LIKE ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(["%$searchTerm%"]);
                } else {
                    // Search in all columns
                    $columns = getTableColumns($pdo, $tableName);
                    $conditions = [];
                    foreach ($columns as $col) {
                        $conditions[] = "`{$col['Field']}` LIKE ?";
                    }
                    $sql = "SELECT * FROM `$tableName` WHERE " . implode(' OR ', $conditions);
                    $stmt = $pdo->prepare($sql);
                    $params = array_fill(0, count($columns), "%$searchTerm%");
                    $stmt->execute($params);
                }
                
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $results, 'count' => count($results)]);
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
    }
}

// Handle download requests
if (isset($_POST['download'])) {
    $downloadType = $_POST['download_type'];
    
    if ($downloadType === 'all_tables') {
        downloadAllTables($pdo);
    } elseif ($downloadType === 'single_table') {
        $tableName = $_POST['table_name'];
        $exportType = $_POST['export_type'] ?? 'all';
        
        if ($exportType === 'all') {
            downloadAllData($pdo, $tableName);
        } elseif ($exportType === 'selected' && isset($_POST['selected_ids'])) {
            downloadSelectedData($pdo, $tableName, $_POST['selected_ids']);
        }
    }
    exit;
}

// Handle raw SQL view request
if (isset($_GET['action']) && $_GET['action'] === 'view_raw_sql' && isset($_GET['table'])) {
    $tableName = $_GET['table'];
    $rawSQL = generateRawSQL($pdo, $tableName);
    
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: inline; filename="' . $tableName . '_raw.sql"');
    echo $rawSQL;
    exit;
}

// Function to get all tables in database
function getAllTables($pdo) {
    try {
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $tables;
    } catch(PDOException $e) {
        return [];
    }
}

// Function to get table columns
function getTableColumns($pdo, $tableName) {
    try {
        $stmt = $pdo->prepare("DESCRIBE `$tableName`");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

// Function to get table info
function getTableInfo($pdo, $tableName) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as row_count FROM `$tableName`");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("DESCRIBE `$tableName`");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'row_count' => $count,
            'column_count' => count($columns),
            'columns' => $columns
        ];
    } catch(PDOException $e) {
        return ['row_count' => 0, 'column_count' => 0, 'columns' => []];
    }
}

// Function to get primary key of table
function getPrimaryKey($pdo, $tableName) {
    try {
        $stmt = $pdo->prepare("SHOW KEYS FROM `$tableName` WHERE Key_name = 'PRIMARY'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['Column_name'] : 'id';
    } catch(PDOException $e) {
        return 'id';
    }
}

// Function to generate raw SQL for a table
function generateRawSQL($pdo, $tableName) {
    try {
        $sqlContent = "-- Raw SQL Export for table: $tableName\n";
        $sqlContent .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
        
        // Get table structure
        $stmt = $pdo->prepare("SHOW CREATE TABLE `$tableName`");
        $stmt->execute();
        $createTable = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $sqlContent .= "-- Table structure for table `$tableName`\n";
        $sqlContent .= "DROP TABLE IF EXISTS `$tableName`;\n";
        $sqlContent .= $createTable['Create Table'] . ";\n\n";
        
        // Get all data
        $stmt = $pdo->prepare("SELECT * FROM `$tableName`");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($data)) {
            $columns = array_keys($data[0]);
            $columnList = '`' . implode('`, `', $columns) . '`';
            
            $sqlContent .= "-- Dumping data for table `$tableName`\n";
            $sqlContent .= "LOCK TABLES `$tableName` WRITE;\n";
            $sqlContent .= "INSERT INTO `$tableName` ($columnList) VALUES\n";
            
            foreach ($data as $index => $row) {
                $values = array_map(function($value) {
                    if ($value === null) return 'NULL';
                    return "'" . addslashes($value) . "'";
                }, array_values($row));
                
                $sqlContent .= "(" . implode(', ', $values) . ")";
                
                if ($index < count($data) - 1) {
                    $sqlContent .= ",\n";
                } else {
                    $sqlContent .= ";\n";
                }
            }
            $sqlContent .= "UNLOCK TABLES;\n\n";
        }
        
        return $sqlContent;
    } catch(PDOException $e) {
        return "-- Error generating SQL for table $tableName: " . $e->getMessage();
    }
}

// Function to download all tables
function downloadAllTables($pdo) {
    try {
        $tables = getAllTables($pdo);
        
        $sqlContent = "-- Full Database Export\n";
        $sqlContent .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
        $sqlContent .= "-- Total tables: " . count($tables) . "\n\n";
        $sqlContent .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $sqlContent .= "START TRANSACTION;\n";
        $sqlContent .= "SET time_zone = \"+00:00\";\n\n";
        
        foreach ($tables as $tableName) {
            $sqlContent .= generateRawSQL($pdo, $tableName);
            $sqlContent .= "\n" . str_repeat("-", 50) . "\n\n";
        }
        
        $sqlContent .= "COMMIT;\n";
        
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="full_database_export_' . date('Y-m-d_H-i-s') . '.sql"');
        header('Content-Length: ' . strlen($sqlContent));
        
        echo $sqlContent;
    } catch(Exception $e) {
        die("Error generating full database export: " . $e->getMessage());
    }
}

// Function to download all data from single table
function downloadAllData($pdo, $tableName) {
    $sqlContent = generateRawSQL($pdo, $tableName);
    
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $tableName . '_complete_' . date('Y-m-d_H-i-s') . '.sql"');
    header('Content-Length: ' . strlen($sqlContent));
    
    echo $sqlContent;
}

// Function to download selected data
function downloadSelectedData($pdo, $tableName, $selectedIds) {
    try {
        $placeholders = str_repeat('?,', count($selectedIds) - 1) . '?';
        $primaryKey = getPrimaryKey($pdo, $tableName);
        $stmt = $pdo->prepare("SELECT * FROM `$tableName` WHERE `$primaryKey` IN ($placeholders)");
        $stmt->execute($selectedIds);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($data)) {
            die("No data found for selected records");
        }
        
        $sqlContent = "-- Selected data export for table: $tableName\n";
        $sqlContent .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
        $sqlContent .= "-- Selected records: " . count($data) . "\n\n";
        
        $columns = array_keys($data[0]);
        $columnList = '`' . implode('`, `', $columns) . '`';
        
        $sqlContent .= "INSERT INTO `$tableName` ($columnList) VALUES\n";
        
        foreach ($data as $index => $row) {
            $values = array_map(function($value) {
                if ($value === null) return 'NULL';
                return "'" . addslashes($value) . "'";
            }, array_values($row));
            
            $sqlContent .= "(" . implode(', ', $values) . ")";
            
            if ($index < count($data) - 1) {
                $sqlContent .= ",\n";
            } else {
                $sqlContent .= ";\n";
            }
        }
        
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $tableName . '_selected_' . date('Y-m-d_H-i-s') . '.sql"');
        header('Content-Length: ' . strlen($sqlContent));
        
        echo $sqlContent;
    } catch(PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}

// Get all tables
$allTables = getAllTables($pdo);

// Get current table data
$currentTable = $_GET['table'] ?? ($allTables[0] ?? '');
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';
$searchColumn = $_GET['search_column'] ?? 'all';

$data = [];
$columns = [];
$columnInfo = [];
$totalRecords = 0;
$totalPages = 0;
$primaryKey = 'id';

if ($currentTable) {
    try {
        // Get primary key
        $primaryKey = getPrimaryKey($pdo, $currentTable);
        
        // Get column information
        $stmt = $pdo->prepare("DESCRIBE `$currentTable`");
        $stmt->execute();
        $columnInfo = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Build search query
        $whereClause = '';
        $params = [];
        
        if ($search) {
            if ($searchColumn && $searchColumn !== 'all') {
                $whereClause = "WHERE `$searchColumn` LIKE ?";
                $params[] = "%$search%";
            } else {
                $conditions = [];
                foreach ($columnInfo as $col) {
                    $conditions[] = "`{$col['Field']}` LIKE ?";
                    $params[] = "%$search%";
                }
                $whereClause = "WHERE " . implode(' OR ', $conditions);
            }
        }
        
        // Get total count
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM `$currentTable` $whereClause");
        $countStmt->execute($params);
        $totalRecords = $countStmt->fetchColumn();
        $totalPages = ceil($totalRecords / $limit);
        
        // Get data for current page
        $stmt = $pdo->prepare("SELECT * FROM `$currentTable` $whereClause LIMIT :limit OFFSET :offset");
        foreach ($params as $index => $param) {
            $stmt->bindValue($index + 1, $param);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get column names
        $columns = !empty($data) ? array_keys($data[0]) : array_column($columnInfo, 'Field');
        
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced SQL Database Manager</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .table-sidebar {
            max-height: 70vh;
            overflow-y: auto;
        }
        .table-item {
            cursor: pointer;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        .table-item:hover {
            background-color: var(--bs-primary-bg-subtle);
            border-left-color: var(--bs-primary);
        }
        .table-item.active {
            background-color: var(--bs-success-bg-subtle);
            border-left-color: var(--bs-success);
        }
        .editable-cell {
            cursor: pointer;
            position: relative;
        }
        .editable-cell:hover {
            background-color: var(--bs-warning-bg-subtle) !important;
        }
        .cell-input {
            border: none;
            background: transparent;
            width: 100%;
            padding: 0;
            margin: 0;
        }
        .cell-input:focus {
            outline: 2px solid var(--bs-primary);
            background: white;
        }
        .insert-row {
            background-color: var(--bs-info-bg-subtle);
        }
        .insert-row input {
            border: 1px solid var(--bs-border-color);
            border-radius: 0.25rem;
            padding: 0.25rem;
        }
        .sticky-header {
            position: sticky;
            top: 0;
            z-index: 10;
            background: white;
        }
        .action-buttons {
            white-space: nowrap;
        }
        .sql-editor {
            font-family: 'Courier New', monospace;
            min-height: 200px;
            background-color: #f8f9fa;
        }
        .search-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1rem;
            margin-bottom: 1rem;
        }
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <!-- Header -->
        <div class="row bg-primary text-white py-3 mb-4">
            <div class="col">
                <h1 class="h3 mb-0"><i class="bi bi-database"></i> Advanced SQL Database Manager</h1>
                <small>Complete database management with search, create, edit, and export functionality</small>
            </div>
        </div>

        <!-- Global Actions -->
        <div class="row mb-4">
            <div class="col">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-globe"></i> Global Database Actions</h5>
                        <div class="row g-2">
                            <div class="col-auto">
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="download" value="1">
                                    <input type="hidden" name="download_type" value="all_tables">
                                    <button type="submit" class="btn btn-danger">
                                        <i class="bi bi-download"></i> Download Complete Database
                                    </button>
                                </form>
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createTableModal">
                                    <i class="bi bi-plus-circle"></i> Create New Table
                                </button>
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#sqlEditorModal">
                                    <i class="bi bi-code-square"></i> SQL Editor
                                </button>
                            </div>
                            <div class="col-auto">
                                <span class="text-muted">Total Tables: <strong><?php echo count($allTables); ?></strong></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3 col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-list"></i> Database Tables</h6>
                    </div>
                    <div class="card-body p-0 table-sidebar">
                        <?php foreach ($allTables as $table): ?>
                            <?php $tableInfo = getTableInfo($pdo, $table); ?>
                            <div class="table-item p-3 border-bottom <?php echo ($table === $currentTable) ? 'active' : ''; ?>" 
                                 onclick="loadTable('<?php echo htmlspecialchars($table); ?>')">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($table); ?></div>
                                        <small class="text-muted">
                                            <i class="bi bi-table"></i> <?php echo number_format($tableInfo['row_count']); ?> rows, 
                                            <?php echo $tableInfo['column_count']; ?> columns
                                        </small>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#" onclick="showTableStructure('<?php echo htmlspecialchars($table); ?>')">
                                                <i class="bi bi-info-circle"></i> Table Structure
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" onclick="addColumn('<?php echo htmlspecialchars($table); ?>')">
                                                <i class="bi bi-plus"></i> Add Column
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" onclick="truncateTable('<?php echo htmlspecialchars($table); ?>')">
                                                <i class="bi bi-trash"></i> Truncate Table
                                            </a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="dropTable('<?php echo htmlspecialchars($table); ?>')">
                                                <i class="bi bi-x-circle"></i> Drop Table
                                            </a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9 col-md-8">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($currentTable): ?>
                    <!-- Search Container -->
                    <div class="search-container">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" id="searchInput" 
                                           placeholder="Search in table..." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" id="searchColumn">
                                    <option value="all" <?php echo ($searchColumn === 'all') ? 'selected' : ''; ?>>All Columns</option>
                                    <?php foreach ($columns as $column): ?>
                                        <option value="<?php echo htmlspecialchars($column); ?>" 
                                                <?php echo ($searchColumn === $column) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($column); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="button" onclick="performSearch()" class="btn btn-primary w-100">
                                    <i class="bi bi-search"></i> Search
                                </button>
                            </div>
                                                    </div>
                        <?php if ($search): ?>
                            <div class="mt-2">
                                <span class="badge bg-info">Search: "<?php echo htmlspecialchars($search); ?>"</span>
                                <a href="?table=<?php echo urlencode($currentTable); ?>" class="btn btn-sm btn-outline-secondary ms-2">
                                    <i class="bi bi-x"></i> Clear
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Table Actions -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="bi bi-table"></i> <?php echo htmlspecialchars($currentTable); ?>
                            </h5>
                            <div class="row g-2">
                                <div class="col-auto">
                                    <button type="button" onclick="downloadTable()" class="btn btn-success btn-sm">
                                        <i class="bi bi-download"></i> Download Table
                                    </button>
                                </div>
                                <div class="col-auto">
                                    <button type="button" onclick="downloadSelected()" class="btn btn-warning btn-sm">
                                        <i class="bi bi-check-square"></i> Download Selected
                                    </button>
                                </div>
                                <div class="col-auto">
                                    <a href="?action=view_raw_sql&table=<?php echo urlencode($currentTable); ?>" 
                                       target="_blank" class="btn btn-info btn-sm">
                                        <i class="bi bi-eye"></i> View Raw SQL
                                    </a>
                                </div>
                                <div class="col-auto">
                                    <button type="button" onclick="toggleInsertRow()" class="btn btn-primary btn-sm" id="insertToggleBtn">
                                        <i class="bi bi-plus-circle"></i> Add New Row
                                    </button>
                                </div>
                                <div class="col-auto">
                                    <button type="button" onclick="showTableStructure('<?php echo htmlspecialchars($currentTable); ?>')" class="btn btn-secondary btn-sm">
                                        <i class="bi bi-info-circle"></i> Structure
                                    </button>
                                </div>
                                <div class="col-auto">
                                    <span id="selected-count" class="badge bg-success d-none">0 selected</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($data) || !empty($columns)): ?>
                        <?php if ($totalRecords > 0): ?>
                            <div class="alert alert-info" role="alert">
                                <i class="bi bi-info-circle"></i> 
                                <?php if ($search): ?>
                                    Found <?php echo count($data); ?> of <?php echo number_format($totalRecords); ?> matching records
                                <?php else: ?>
                                    Showing <?php echo count($data); ?> of <?php echo number_format($totalRecords); ?> records 
                                    (Page <?php echo $page; ?> of <?php echo $totalPages; ?>)
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="card">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-dark sticky-header">
                                            <tr>
                                                <th style="width: 50px;">
                                                    <input type="checkbox" class="form-check-input" id="select-all" onchange="toggleAll()">
                                                </th>
                                                <?php foreach ($columns as $column): ?>
                                                    <th>
                                                        <?php echo htmlspecialchars($column); ?>
                                                        <div class="dropdown d-inline">
                                                            <button class="btn btn-sm btn-link text-white" type="button" data-bs-toggle="dropdown">
                                                                <i class="bi bi-three-dots"></i>
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                                <li><a class="dropdown-item" href="#" onclick="dropColumn('<?php echo htmlspecialchars($currentTable); ?>', '<?php echo htmlspecialchars($column); ?>')">
                                                                    <i class="bi bi-trash"></i> Drop Column
                                                                </a></li>
                                                            </ul>
                                                        </div>
                                                    </th>
                                                <?php endforeach; ?>
                                                <th style="width: 120px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Insert Row (Hidden by default) -->
                                            <tr id="insert-row" class="insert-row d-none">
                                                <td>
                                                    <button type="button" onclick="insertRow()" class="btn btn-success btn-sm">
                                                        <i class="bi bi-check"></i>
                                                    </button>
                                                </td>
                                                <?php foreach ($columns as $column): ?>
                                                    <?php 
                                                    $colInfo = array_filter($columnInfo, function($col) use ($column) {
                                                        return $col['Field'] === $column;
                                                    });
                                                    $colInfo = reset($colInfo);
                                                    $isAutoIncrement = strpos($colInfo['Extra'] ?? '', 'auto_increment') !== false;
                                                    ?>
                                                    <td>
                                                        <?php if ($isAutoIncrement): ?>
                                                            <input type="text" class="form-control form-control-sm insert-input" 
                                                                   data-column="<?php echo htmlspecialchars($column); ?>" 
                                                                   placeholder="AUTO" readonly>
                                                        <?php else: ?>
                                                            <input type="text" class="form-control form-control-sm insert-input" 
                                                                   data-column="<?php echo htmlspecialchars($column); ?>" 
                                                                   placeholder="Enter <?php echo htmlspecialchars($column); ?>">
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endforeach; ?>
                                                <td>
                                                    <button type="button" onclick="cancelInsert()" class="btn btn-secondary btn-sm">
                                                        <i class="bi bi-x"></i>
                                                    </button>
                                                </td>
                                            </tr>

                                            <!-- Data Rows -->
                                            <?php foreach ($data as $row): ?>
                                                <tr data-id="<?php echo htmlspecialchars($row[$primaryKey] ?? ''); ?>">
                                                    <td>
                                                        <input type="checkbox" class="form-check-input row-checkbox" 
                                                               value="<?php echo htmlspecialchars($row[$primaryKey] ?? ''); ?>" 
                                                               onchange="updateSelectedCount()">
                                                    </td>
                                                    <?php foreach ($columns as $column): ?>
                                                        <td class="editable-cell" 
                                                            data-column="<?php echo htmlspecialchars($column); ?>"
                                                            data-id="<?php echo htmlspecialchars($row[$primaryKey] ?? ''); ?>"
                                                            onclick="editCell(this)">
                                                            <?php echo htmlspecialchars($row[$column] ?? ''); ?>
                                                        </td>
                                                    <?php endforeach; ?>
                                                    <td class="action-buttons">
                                                        <button type="button" onclick="deleteRow('<?php echo htmlspecialchars($row[$primaryKey] ?? ''); ?>')" 
                                                                class="btn btn-danger btn-sm">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1 && !$search): ?>
                            <nav aria-label="Table pagination" class="mt-3">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?table=<?php echo urlencode($currentTable); ?>&page=<?php echo ($page - 1); ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?table=<?php echo urlencode($currentTable); ?>&page=<?php echo $i; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?table=<?php echo urlencode($currentTable); ?>&page=<?php echo ($page + 1); ?>">Next</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="alert alert-info" role="alert">
                            <i class="bi bi-info-circle"></i> No data found in table "<?php echo htmlspecialchars($currentTable); ?>"
                            <?php if ($search): ?>
                                matching your search criteria.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-arrow-left"></i> Select a table from the sidebar to view its data
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Create Table Modal -->
    <div class="modal fade" id="createTableModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Create New Table</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="createTableForm">
                        <div class="mb-3">
                            <label for="tableName" class="form-label">Table Name</label>
                            <input type="text" class="form-control" id="tableName" required>
                        </div>
                        <h6>Columns:</h6>
                        <div id="columnsContainer">
                            <div class="row mb-2 column-row">
                                <div class="col-md-3">
                                    <input type="text" class="form-control" placeholder="Column Name" name="column_name[]" required>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select" name="column_type[]">
                                        <option value="VARCHAR">VARCHAR</option>
                                        <option value="INT">INT</option>
                                        <option value="TEXT">TEXT</option>
                                        <option value="DATETIME">DATETIME</option>
                                        <option value="DATE">DATE</option>
                                        <option value="DECIMAL">DECIMAL</option>
                                        <option value="BOOLEAN">BOOLEAN</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="text" class="form-control" placeholder="Length" name="column_length[]">
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select" name="column_null[]">
                                        <option value="YES">NULL</option>
                                        <option value="NO">NOT NULL</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="text" class="form-control" placeholder="Default" name="column_default[]">
                                </div>
                                <div class="col-md-1">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="column_primary[]" title="Primary Key">
                                        <label class="form-check-label">PK</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="addColumn()">
                            <i class="bi bi-plus"></i> Add Column
                        </button>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="createTable()">Create Table</button>
                </div>
            </div>
        </div>
    </div>

    <!-- SQL Editor Modal -->
    <div class="modal fade" id="sqlEditorModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-code-square"></i> SQL Editor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <textarea class="form-control sql-editor" id="sqlQuery" placeholder="Enter your SQL query here..."></textarea>
                    <div class="mt-3">
                        <button type="button" class="btn btn-primary" onclick="executeQuery()">
                            <i class="bi bi-play"></i> Execute Query
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="clearQuery()">
                            <i class="bi bi-x-circle"></i> Clear
                        </button>
                    </div>
                    <div id="queryResults" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Structure Modal -->
    <div class="modal fade" id="tableStructureModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-info-circle"></i> Table Structure</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="tableStructureContent"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Column Modal -->
    <div class="modal fade" id="addColumnModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus"></i> Add Column</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addColumnForm">
                        <input type="hidden" id="addColumnTableName">
                        <div class="mb-3">
                            <label for="newColumnName" class="form-label">Column Name</label>
                            <input type="text" class="form-control" id="newColumnName" required>
                        </div>
                        <div class="mb-3">
                            <label for="newColumnType" class="form-label">Data Type</label>
                            <select class="form-select" id="newColumnType">
                                <option value="VARCHAR">VARCHAR</option>
                                <option value="INT">INT</option>
                                <option value="TEXT">TEXT</option>
                                <option value="DATETIME">DATETIME</option>
                                <option value="DATE">DATE</option>
                                <option value="DECIMAL">DECIMAL</option>
                                <option value="BOOLEAN">BOOLEAN</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="newColumnLength" class="form-label">Length (optional)</label>
                            <input type="text" class="form-control" id="newColumnLength">
                        </div>
                        <div class="mb-3">
                            <label for="newColumnNull" class="form-label">Allow NULL</label>
                            <select class="form-select" id="newColumnNull">
                                <option value="YES">YES</option>
                                <option value="NO">NO</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="newColumnDefault" class="form-label">Default Value (optional)</label>
                            <input type="text" class="form-control" id="newColumnDefault">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitAddColumn()">Add Column</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden form for downloads -->
    <form method="post" id="downloadForm" class="d-none">
        <input type="hidden" name="table_name" value="<?php echo htmlspecialchars($currentTable); ?>">
    </form>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        const currentTable = '<?php echo htmlspecialchars($currentTable); ?>';
        const primaryKey = '<?php echo htmlspecialchars($primaryKey); ?>';

        function loadTable(tableName) {
            window.location.href = '?table=' + encodeURIComponent(tableName);
        }

        function performSearch() {
            const searchTerm = document.getElementById('searchInput').value;
            const searchColumn = document.getElementById('searchColumn').value;
            
            let url = '?table=' + encodeURIComponent(currentTable);
            if (searchTerm) {
                url += '&search=' + encodeURIComponent(searchTerm);
                url += '&search_column=' + encodeURIComponent(searchColumn);
            }
            
            window.location.href = url;
        }

        // Search on Enter key
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });

        function toggleAll() {
            const selectAll = document.getElementById('select-all');
            const checkboxes = document.querySelectorAll('.row-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
            const count = checkedBoxes.length;
            const countElement = document.getElementById('selected-count');
            
            if (count > 0) {
                countElement.textContent = count + ' selected';
                countElement.classList.remove('d-none');
            } else {
                countElement.classList.add('d-none');
            }
        }

        function editCell(cell) {
            if (cell.querySelector('.cell-input')) return;
            
            const originalValue = cell.textContent;
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'cell-input form-control form-control-sm';
            input.value = originalValue;
            
            cell.innerHTML = '';
            cell.appendChild(input);
            input.focus();
            input.select();
            
            const saveEdit = () => {
                const newValue = input.value;
                const column = cell.dataset.column;
                const id = cell.dataset.id;
                
                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=update_cell&table=${currentTable}&column=${column}&value=${encodeURIComponent(newValue)}&id=${id}&primary_key=${primaryKey}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        cell.textContent = newValue;
                        showToast('Cell updated successfully', 'success');
                    } else {
                        cell.textContent = originalValue;
                        showToast('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    cell.textContent = originalValue;
                    showToast('Error updating cell', 'error');
                });
            };
            
            const cancelEdit = () => {
                cell.textContent = originalValue;
            };
            
            input.addEventListener('blur', saveEdit);
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') saveEdit();
                if (e.key === 'Escape') cancelEdit();
            });
        }

        function deleteRow(id) {
            if (!confirm('Are you sure you want to delete this row?')) return;
            
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=delete_row&table=${currentTable}&id=${id}&primary_key=${primaryKey}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelector(`tr[data-id="${id}"]`).remove();
                    showToast('Row deleted successfully', 'success');
                    updateSelectedCount();
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error deleting row', 'error');
            });
        }

        function toggleInsertRow() {
            const insertRow = document.getElementById('insert-row');
            const toggleBtn = document.getElementById('insertToggleBtn');
            
            if (insertRow.classList.contains('d-none')) {
                insertRow.classList.remove('d-none');
                toggleBtn.innerHTML = '<i class="bi bi-x-circle"></i> Cancel Add';
                toggleBtn.classList.replace('btn-primary', 'btn-secondary');
            } else {
                insertRow.classList.add('d-none');
                toggleBtn.innerHTML = '<i class="bi bi-plus-circle"></i> Add New Row';
                toggleBtn.classList.replace('btn-secondary', 'btn-primary');
                clearInsertInputs();
            }
        }

        function clearInsertInputs() {
            document.querySelectorAll('.insert-input').forEach(input => {
                if (!input.readOnly) input.value = '';
            });
        }

        function cancelInsert() {
            toggleInsertRow();
        }

        function insertRow() {
            const inputs = document.querySelectorAll('.insert-input:not([readonly])');
            const data = {};
            let hasData = false;
            
            inputs.forEach(input => {
                const column = input.dataset.column;
                const value = input.value.trim();
                if (value !== '') {
                    data[column] = value;
                    hasData = true;
                }
            });
            
            if (!hasData) {
                showToast('Please enter at least one value', 'warning');
                return;
            }
            
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=insert_row&table=${currentTable}&data=${encodeURIComponent(JSON.stringify(data))}`
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showToast('Row inserted successfully', 'success');
                    clearInsertInputs();
                    window.location.reload();
                } else {
                    showToast('Error: ' + result.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error inserting row', 'error');
            });
        }

        // Create Table Functions
        function addColumnToForm() {
            const container = document.getElementById('columnsContainer');
            const newRow = document.createElement('div');
            newRow.className = 'row mb-2 column-row';
            newRow.innerHTML = `
                <div class="col-md-3">
                    <input type="text" class="form-control" placeholder="Column Name" name="column_name[]" required>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="column_type[]">
                        <option value="VARCHAR">VARCHAR</option>
                        <option value="INT">INT</option>
                        <option value="TEXT">TEXT</option>
                        <option value="DATETIME">DATETIME</option>
                        <option value="DATE">DATE</option>
                        <option value="DECIMAL">DECIMAL</option>
                        <option value="BOOLEAN">BOOLEAN</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="text" class="form-control" placeholder="Length" name="column_length[]">
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="column_null[]">
                        <option value="YES">NULL</option>
                        <option value="NO">NOT NULL</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="text" class="form-control" placeholder="Default" name="column_default[]">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeColumnRow(this)">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(newRow);
        }

        function removeColumnRow(button) {
            button.closest('.column-row').remove();
        }

        function createTable() {
            const tableName = document.getElementById('tableName').value;
            const columnRows = document.querySelectorAll('.column-row');
            const columns = [];
            
            columnRows.forEach(row => {
                const name = row.querySelector('input[name="column_name[]"]').value;
                const type = row.querySelector('select[name="column_type[]"]').value;
                const length = row.querySelector('input[name="column_length[]"]').value;
                const nullValue = row.querySelector('select[name="column_null[]"]').value;
                const defaultValue = row.querySelector('input[name="column_default[]"]').value;
                const primary = row.querySelector('input[name="column_primary[]"]')?.checked || false;
                
                if (name) {
                    columns.push({
                        name: name,
                        type: type,
                        length: length,
                        null: nullValue,
                        default: defaultValue,
                        primary: primary,
                        auto_increment: primary && type === 'INT'
                    });
                }
            });
            
            if (!tableName || columns.length === 0) {
                showToast('Please provide table name and at least one column', 'warning');
                return;
            }
            
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=create_table&table_name=${encodeURIComponent(tableName)}&columns=${encodeURIComponent(JSON.stringify(columns))}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Table created successfully', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('createTableModal')).hide();
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error creating table', 'error');
            });
        }

        // SQL Editor Functions
        function executeQuery() {
            const query = document.getElementById('sqlQuery').value;
            
            if (!query.trim()) {
                showToast('Please enter a SQL query', 'warning');
                return;
            }
            
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=execute_query&query=${encodeURIComponent(query)}`
            })
            .then(response => response.json())
            .then(data => {
                const resultsDiv = document.getElementById('queryResults');
                
                if (data.success) {
                    let html = '<div class="alert alert-success">Query executed successfully</div>';
                    
                    if (data.data) {
                        // SELECT query results
                        html += '<div class="table-responsive"><table class="table table-sm table-striped">';
                        if (data.data.length > 0) {
                            // Headers
                            html += '<thead class="table-dark"><tr>';
                            Object.keys(data.data[0]).forEach(key => {
                                html += `<th>${key}</th>`;
                            });
                            html += '</tr></thead><tbody>';
                            
                            // Data
                            data.data.forEach(row => {
                                html += '<tr>';
                                Object.values(row).forEach(value => {
                                    html += `<td>${value || ''}</td>`;
                                });
                                html += '</tr>';
                            });
                        } else {
                            html += '<tr><td colspan="100%">No results found</td></tr>';
                        }
                        html += '</tbody></table></div>';
                    } else if (data.affected_rows !== undefined) {
                        // Non-SELECT query results
                        html += `<p>Affected rows: ${data.affected_rows}</p>`;
                    }
                    
                    resultsDiv.innerHTML = html;
                    showToast('Query executed successfully', 'success');
                } else {
                    resultsDiv.innerHTML = `<div class="alert alert-danger">Error: ${data.message}</div>`;
                    showToast('Query failed', 'error');
                }
            })
            .catch(error => {
                showToast('Error executing query', 'error');
                document.getElementById('queryResults').innerHTML = `<div class="alert alert-danger">Network error</div>`;
            });
        }

        function clearQuery() {
            document.getElementById('sqlQuery').value = '';
            document.getElementById('queryResults').innerHTML = '';
        }

        // Table Structure Functions
        function showTableStructure(tableName) {
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=get_table_structure&table_name=${encodeURIComponent(tableName)}`
            })
            .then(response => {
                if (!response.ok) {
                    // If the action doesn't exist, get structure via page reload with DESCRIBE
                    const content = document.getElementById('tableStructureContent');
                    content.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div></div>';
                    
                    // Simulate table structure display
                    setTimeout(() => {
                        content.innerHTML = `
                            <p>Table structure for: <strong>${tableName}</strong></p>
                            <p class="text-muted">To view complete table structure, use the SQL Editor with: <code>DESCRIBE \`${tableName}\`</code></p>
                        `;
                    }, 500);
                }
                return response.json();
            })
            .then(data => {
                if (data && data.success) {
                    // Handle structure data if implemented
                    document.getElementById('tableStructureContent').innerHTML = data.html;
                }
            })
            .catch(error => {
                console.log('Structure fetch failed, showing basic info');
            });
            
            new bootstrap.Modal(document.getElementById('tableStructureModal')).show();
        }

        // Table Management Functions
        function dropTable(tableName) {
            if (!confirm(`Are you sure you want to DROP the table "${tableName}"? This action cannot be undone!`)) {
                return;
            }
            
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=drop_table&table_name=${encodeURIComponent(tableName)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Table dropped successfully', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error dropping table', 'error');
            });
        }

        function truncateTable(tableName) {
            if (!confirm(`Are you sure you want to TRUNCATE the table "${tableName}"? All data will be deleted!`)) {
                return;
            }
            
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=truncate_table&table_name=${encodeURIComponent(tableName)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Table truncated successfully', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error truncating table', 'error');
            });
        }

        function addColumn(tableName) {
            document.getElementById('addColumnTableName').value = tableName;
            new bootstrap.Modal(document.getElementById('addColumnModal')).show();
        }

        function submitAddColumn() {
            const tableName = document.getElementById('addColumnTableName').value;
            const columnName = document.getElementById('newColumnName').value;
            const columnType = document.getElementById('newColumnType').value;
            const columnLength = document.getElementById('newColumnLength').value;
            const columnNull = document.getElementById('newColumnNull').value;
            const columnDefault = document.getElementById('newColumnDefault').value;
            
            if (!columnName) {
                showToast('Please enter column name', 'warning');
                return;
            }
            
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=add_column&table_name=${encodeURIComponent(tableName)}&column_name=${encodeURIComponent(columnName)}&column_type=${columnType}&column_length=${columnLength}&column_null=${columnNull}&column_default=${encodeURIComponent(columnDefault)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Column added successfully', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('addColumnModal')).hide();
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error adding column', 'error');
            });
        }

        function dropColumn(tableName, columnName) {
            if (!confirm(`Are you sure you want to DROP the column "${columnName}" from table "${tableName}"?`)) {
                return;
            }
            
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=drop_column&table_name=${encodeURIComponent(tableName)}&column_name=${encodeURIComponent(columnName)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Column dropped successfully', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error dropping column', 'error');
            });
        }

        // Download Functions
        function downloadTable() {
            const form = document.getElementById('downloadForm');
            clearFormInputs(form);
            
            addFormInput(form, 'download', '1');
            addFormInput(form, 'download_type', 'single_table');
            addFormInput(form, 'export_type', 'all');
            
            form.submit();
        }

        function downloadSelected() {
            const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
            
            if (checkedBoxes.length === 0) {
                showToast('Please select at least one row to download', 'warning');
                return;
            }
            
            const form = document.getElementById('downloadForm');
            clearFormInputs(form);
            
            addFormInput(form, 'download', '1');
            addFormInput(form, 'download_type', 'single_table');
            addFormInput(form, 'export_type', 'selected');
            
            checkedBoxes.forEach(checkbox => {
                addFormInput(form, 'selected_ids[]', checkbox.value);
            });
            
            form.submit();
        }

        function clearFormInputs(form) {
            const inputs = form.querySelectorAll('input[name="download"], input[name="download_type"], input[name="export_type"], input[name="selected_ids[]"]');
            inputs.forEach(input => input.remove());
        }

        function addFormInput(form, name, value) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        }

        // Utility Functions
        function showToast(message, type = 'info') {
            // Remove existing toasts
            document.querySelectorAll('.custom-toast').forEach(toast => toast.remove());
            
            const toastContainer = document.createElement('div');
            toastContainer.className = 'position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '1080';
            
            const alertClass = {
                'success': 'alert-success',
                'error': 'alert-danger',
                'warning': 'alert-warning',
                'info': 'alert-info'
            }[type] || 'alert-info';
            
            const icon = {
                'success': 'bi-check-circle',
                'error': 'bi-exclamation-triangle',
                'warning': 'bi-exclamation-circle',
                'info': 'bi-info-circle'
            }[type] || 'bi-info-circle';
            
            toastContainer.innerHTML = `
                <div class="alert ${alertClass} alert-dismissible fade show custom-toast" role="alert">
                    <i class="bi ${icon}"></i> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            document.body.appendChild(toastContainer);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                toastContainer.remove();
            }, 5000);
        }

        // Initialize
        updateSelectedCount();

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + A to select all rows
            if ((e.ctrlKey || e.metaKey) && e.key === 'a' && !e.target.classList.contains('cell-input')) {
                e.preventDefault();
                document.getElementById('select-all').checked = true;
                toggleAll();
            }
            
            // Escape to cancel insert
            if (e.key === 'Escape') {
                const insertRow = document.getElementById('insert-row');
                if (insertRow && !insertRow.classList.contains('d-none')) {
                    cancelInsert();
                }
            }
            
            // Ctrl/Cmd + Enter to execute SQL query
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter' && document.getElementById('sqlEditorModal').classList.contains('show')) {
                e.preventDefault();
                executeQuery();
            }
        });

        // Handle insert form submission on Enter
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && e.target.classList.contains('insert-input')) {
                e.preventDefault();
                insertRow();
            }
        });

        // Clear modal forms when closed
        document.getElementById('createTableModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('createTableForm').reset();
            document.getElementById('columnsContainer').innerHTML = `
                <div class="row mb-2 column-row">
                    <div class="col-md-3">
                        <input type="text" class="form-control" placeholder="Column Name" name="column_name[]" required>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="column_type[]">
                            <option value="VARCHAR">VARCHAR</option>
                            <option value="INT">INT</option>
                            <option value="TEXT">TEXT</option>
                            <option value="DATETIME">DATETIME</option>
                            <option value="DATE">DATE</option>
                            <option value="DECIMAL">DECIMAL</option>
                            <option value="BOOLEAN">BOOLEAN</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="text" class="form-control" placeholder="Length" name="column_length[]">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="column_null[]">
                            <option value="YES">NULL</option>
                            <option value="NO">NOT NULL</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="text" class="form-control" placeholder="Default" name="column_default[]">
                    </div>
                    <div class="col-md-1">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="column_primary[]" title="Primary Key">
                            <label class="form-check-label">PK</label>
                        </div>
                    </div>
                </div>
            `;
        });

        document.getElementById('addColumnModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('addColumnForm').reset();
        });

        // Add the missing addColumn function for create table modal
        function addColumn() {
            addColumnToForm();
        }

        // Auto-complete for SQL Editor
        document.getElementById('sqlQuery').addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                e.preventDefault();
                const start = this.selectionStart;
                const end = this.selectionEnd;
                this.value = this.value.substring(0, start) + '    ' + this.value.substring(end);
                this.selectionStart = this.selectionEnd = start + 4;
            }
        });

        // Add sample queries to SQL Editor
        function insertSampleQuery(type) {
            const textarea = document.getElementById('sqlQuery');
            let query = '';
            
            switch(type) {
                case 'select':
                    query = `SELECT * FROM \`${currentTable}\` LIMIT 10;`;
                    break;
                case 'insert':
                    query = `INSERT INTO \`${currentTable}\` (column1, column2) VALUES ('value1', 'value2');`;
                    break;
                case 'update':
                    query = `UPDATE \`${currentTable}\` SET column1 = 'new_value' WHERE id = 1;`;
                    break;
                case 'delete':
                    query = `DELETE FROM \`${currentTable}\` WHERE id = 1;`;
                    break;
            }
            
            textarea.value = query;
            textarea.focus();
        }

        // Add sample query buttons to SQL Editor
        document.addEventListener('DOMContentLoaded', function() {
            const sqlEditorModal = document.getElementById('sqlEditorModal');
            if (sqlEditorModal && currentTable) {
                const modalBody = sqlEditorModal.querySelector('.modal-body');
                const sampleButtons = document.createElement('div');
                sampleButtons.className = 'mb-3';
                sampleButtons.innerHTML = `
                    <small class="text-muted">Sample queries:</small><br>
                    <button type="button" class="btn btn-outline-secondary btn-sm me-1" onclick="insertSampleQuery('select')">SELECT</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm me-1" onclick="insertSampleQuery('insert')">INSERT</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm me-1" onclick="insertSampleQuery('update')">UPDATE</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm me-1" onclick="insertSampleQuery('delete')">DELETE</button>
                `;
                modalBody.insertBefore(sampleButtons, modalBody.firstChild.nextSibling);
            }
        });
    </script>
</body>
</html>