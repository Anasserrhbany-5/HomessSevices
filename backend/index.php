<?php
// In production, avoid sending raw PHP errors as HTML to clients.
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');
header('Content-Type: application/json');
require_once 'middleware/cors.php';
CorsMiddleware::handle();
$request_method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];
$request_uri = explode('?', $request_uri)[0];
$request_uri = ltrim($request_uri, '/');
if (strpos($request_uri, 'gestion-travaux-domiciles4/') !== false) {
    $request_uri = substr($request_uri, strpos($request_uri, 'gestion-travaux-domiciles4/') + 25);
}
if (strpos($request_uri, 'backend/') !== false) {
    $request_uri = substr($request_uri, strpos($request_uri, 'backend/') + 8);
}
if (strpos($request_uri, 'index.php/') !== false) {
    $request_uri = str_replace('index.php/', '', $request_uri);
}
if ((empty($request_uri) || $request_uri === 'index.php' || $request_uri === 'backend/index.php') && isset($_GET['route'])) {
    $request_uri = trim($_GET['route'], '/');
}
$uri_parts = explode('/', $request_uri);
if ($uri_parts[0] === 'api') {
    array_shift($uri_parts); 
    $isPublic = ($uri_parts[0] === 'public');
    if ($isPublic) {
        array_shift($uri_parts); 
    }
    $endpoint = $uri_parts[0] ?? '';
    $sub = $uri_parts[1] ?? null;
    $id = $uri_parts[2] ?? null;
    switch ($endpoint) {
        case 'register':
            if ($request_method === 'POST') {
                require_once 'controllers/AuthController.php';
                $controller = new AuthController();
                $controller->register();
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;
        case 'login':
            if ($request_method === 'POST') {
                require_once 'controllers/AuthController.php';
                $controller = new AuthController();
                $controller->login();
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;
        case 'requests':
            require_once 'controllers/ClientController.php';
            $controller = new ClientController();
            if ($request_method === 'POST') {
                $controller->createRequest();
            } elseif ($request_method === 'GET') {
                if ($sub) {
                    $controller->getRequest($sub);
                } else {
                    $controller->getRequests();
                }
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;
        case 'reviews':
            if ($request_method === 'POST') {
                require_once 'controllers/ClientController.php';
                $controller = new ClientController();
                $controller->createReview();
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;
        case 'jobs':
            require_once 'controllers/WorkerController.php';
            $controller = new WorkerController();
            if ($request_method === 'GET') {
                if ($sub === 'available') {
                    $controller->getAvailableJobs();
                } elseif ($sub === 'assigned') {
                    $controller->getAssignedJobs();
                }
            } elseif ($request_method === 'POST') {
                if ($sub === 'accept') {
                    $controller->acceptJob();
                } elseif ($sub === 'reject') {
                    $controller->rejectJob();
                }
            } elseif ($request_method === 'PUT' && $sub === 'status') {
                $controller->updateJobStatus();
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;
        case 'users':
            require_once 'controllers/AdminController.php';
            $controller = new AdminController();
            if ($request_method === 'GET') {
                $controller->getUsers();
            } elseif ($request_method === 'PUT' && $sub) {
                $controller->updateUser($sub);
            } elseif ($request_method === 'DELETE' && $sub) {
                $controller->deleteUser($sub);
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;
        case 'services':
            require_once 'controllers/AdminController.php';
            $controller = new AdminController();
            if ($request_method === 'GET') {
                $controller->getServices();
            } elseif ($request_method === 'POST') {
                $controller->createService();
            } elseif ($request_method === 'PUT' && $sub) {
                $controller->updateService($sub);
            } elseif ($request_method === 'DELETE' && $sub) {
                $controller->deleteService($sub);
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;
        case 'admin':
            if ($sub === 'requests' && $request_method === 'GET') {
                require_once 'controllers/AdminController.php';
                $controller = new AdminController();
                $controller->getRequests();
            } elseif ($sub === 'jobs' && $request_method === 'GET') {
                require_once 'controllers/AdminController.php';
                $controller = new AdminController();
                $controller->getJobs();
            } elseif ($sub === 'support') {
                if ($request_method === 'GET') {
                    require_once 'controllers/AdminSupportController.php';
                    $controller = new AdminSupportController();
                    $controller->getSupportRequests();
                } else {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                }
            } elseif ($sub === 'support-export') {
                if ($request_method === 'GET') {
                    require_once 'controllers/AdminSupportController.php';
                    $controller = new AdminSupportController();
                    $controller->getSupportRequestsExport();
                } else {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                }
            } elseif ($sub === 'support-clear') {
                if ($request_method === 'DELETE') {
                    require_once 'controllers/AdminSupportController.php';
                    $controller = new AdminSupportController();
                    $controller->clearSupportRequests();
                } else {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                }
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Admin endpoint not found']);
            }
            break;
        case 'messages':
            require_once 'controllers/ChatController.php';
            $controller = new ChatController();
            if ($request_method === 'POST') {
                $controller->sendMessage();
            } elseif ($request_method === 'GET' && $sub) {
                $controller->getMessages($sub);
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;
        case 'conversations':
            require_once 'controllers/ChatController.php';
            $controller = new ChatController();
            if ($request_method === 'GET') {
                $controller->getConversations();
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;
        case 'workers':
            require_once 'controllers/ChatController.php';
            $controller = new ChatController();
            if ($request_method === 'GET') {
                $controller->getWorkers();
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;
        case 'notifications':
            require_once 'controllers/NotificationController.php';
            $controller = new NotificationController();
            if ($request_method === 'GET') {
                if ($sub) {
                    if ($sub === 'unread-count') {
                        $controller->getUnreadCount();
                    } else {
                        $controller->getNotifications();
                    }
                } else {
                    $controller->getNotifications();
                }
            } elseif ($request_method === 'PUT') {
                if ($sub === 'mark-all-read') {
                    $controller->markAllAsRead();
                } elseif ($sub) {
                    $controller->markAsRead($sub);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid request']);
                }
            } elseif ($request_method === 'DELETE' && $sub) {
                $controller->deleteNotification($sub);
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;
        case 'stats':
            if ($request_method === 'GET') {
                require_once 'controllers/StatsController.php';
                $controller = new StatsController();
                if ($isPublic) {
                    $controller->getPublicStats();
                } else {
                    $controller->getStats();
                }
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;
        case 'profile':
            require_once 'controllers/UserController.php';
            $controller = new UserController();
            if ($request_method === 'GET') {
                $controller->getProfile();
            } elseif ($request_method === 'PUT') {
                $controller->updateProfile();
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;
        case 'change-password':
            if ($request_method === 'POST') {
                require_once 'controllers/UserController.php';
                $controller = new UserController();
                $controller->changePassword();
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;
        case 'support':
            if ($sub === 'send-email') {
                if ($request_method === 'POST') {
                    require_once 'controllers/SupportController.php';
                    $controller = new SupportController();
                    $controller->sendSupportEmail();
                } else {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                }
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Support endpoint not found']);
            }
            break;
        case 'revenue':
            require_once 'controllers/RevenueController.php';
            $controller = new RevenueController();
            if ($request_method === 'GET') {
                if ($sub === 'stats') {
                    $controller->getPlatformRevenueStats();
                } elseif ($sub === 'worker-stats') {
                    $controller->getWorkerRevenueStats();
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Revenue endpoint not found']);
                }
            } elseif ($request_method === 'PUT' && $sub === 'platform-fee') {
                $controller->updatePlatformFee();
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;
        case 'client':
            require_once 'controllers/ClientController.php';
            $controller = new ClientController();
            if ($sub === 'requests') {
                if ($request_method === 'POST') {
                    $controller->createRequest();
                } elseif ($request_method === 'GET') {
                    if ($id) {
                        $controller->getRequest($id);
                    } else {
                        $controller->getRequests();
                    }
                } else {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                }
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Client endpoint not found']);
            }
            break;
        case 'worker':
            require_once 'controllers/WorkerController.php';
            $controller = new WorkerController();
            if ($sub === 'jobs') {
                if ($request_method === 'GET') {
                    if ($id === 'available') {
                        $controller->getAvailableJobs();
                    } elseif ($id === 'assigned') {
                        $controller->getAssignedJobs();
                    } else {
                        $controller->getAssignedJobs();
                    }
                } elseif ($request_method === 'POST') {
                    if ($id === 'accept') {
                        $controller->acceptJob();
                    } elseif ($id === 'reject') {
                        $controller->rejectJob();
                    } else {
                        http_response_code(405);
                        echo json_encode(['error' => 'Method not allowed']);
                    }
                } else {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                }
            } elseif ($sub === 'reviews') {
                if ($request_method === 'GET') {
                    require_once 'controllers/WorkerController.php';
                    $controller = new WorkerController();
                    $controller->getReviews();
                } else {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                }
            } elseif ($sub === 'stats') {
                if ($request_method === 'GET') {
                    require_once 'controllers/StatsController.php';
                    $statsController = new StatsController();
                    $statsController->getStats();
                } else {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                }
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Worker endpoint not found']);
            }
            break;
        case 'chat':
            require_once 'controllers/ChatController.php';
            $controller = new ChatController();
            if ($request_method === 'GET') {
                $controller->getConversations();
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
            break;
    }
} else {
    http_response_code(404);
    echo json_encode(['error' => 'API not found']);
}
?>