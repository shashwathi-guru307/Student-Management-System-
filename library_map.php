<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}
$dashboard_link = ($_SESSION['role'] === 'admin') ? 'admin_dashboard.php' : 'student_dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Floor Plan</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background-color: var(--bg-color);
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .header-bar {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .map-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            overflow: hidden;
        }
        .map-wrapper {
            position: relative;
            max-width: 1000px;
            width: 100%;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            background: white;
            overflow: hidden;
        }
        .map-image {
            width: 100%;
            height: auto;
            display: block;
        }
        
        .hotspot {
            position: absolute;
            width: 30px;
            height: 30px;
            background: var(--secondary-color);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            box-shadow: 0 0 0 5px rgba(236, 72, 153, 0.3); /* Pink styling */
            transition: all 0.3s ease;
            animation: pulse-ring 2s infinite;
            z-index: 10;
        }
        
        @keyframes pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(236, 72, 153, 0.6); }
            70% { box-shadow: 0 0 0 10px rgba(236, 72, 153, 0); }
            100% { box-shadow: 0 0 0 0 rgba(236, 72, 153, 0); }
        }
        
        .hotspot:hover {
            transform: translate(-50%, -50%) scale(1.1);
            background: var(--primary-color);
            z-index: 20;
        }

        .hotspot-tooltip {
            position: absolute;
            bottom: 150%;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            color: var(--text-color);
            padding: 1rem;
            border-radius: 8px;
            width: 250px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            pointer-events: none;
        }
        
        .hotspot-tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border-width: 8px;
            border-style: solid;
            border-color: white transparent transparent transparent;
        }
        
        .hotspot:hover .hotspot-tooltip {
            opacity: 1;
            visibility: visible;
            bottom: 130%;
        }

        .hotspot-tooltip h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
            color: var(--secondary-color);
        }
        
        .hotspot-tooltip p {
            margin: 0;
            font-size: 0.875rem;
            color: var(--text-muted);
            line-height: 1.4;
        }

        #spot-quiet { top: 30%; left: 20%; }
        #spot-lab { top: 45%; left: 80%; }
        #spot-ref { top: 60%; left: 40%; }
        #spot-lounge { top: 75%; left: 70%; }
        
    </style>
</head>
<body>
    <header class="header-bar">
        <h2>Library Floor Plan</h2>
        <a href="<?php echo htmlspecialchars($dashboard_link); ?>" class="btn btn-outline">
            <i class='bx bx-arrow-back'></i> Back to Dashboard
        </a>
    </header>

    <div class="map-container">
        <div class="map-wrapper">
            <img src="img/library_map.png" alt="Library Floor Plan" class="map-image">
            
            <div class="hotspot" id="spot-quiet">
                <i class='bx bx-volume-mute'></i>
                <div class="hotspot-tooltip">
                    <h3>Quiet Study Zone</h3>
                    <p>Strictly silent area for focused studying. Features individual cubicles with power outlets.</p>
                </div>
            </div>

            <div class="hotspot" id="spot-lab">
                <i class='bx bx-desktop'></i>
                <div class="hotspot-tooltip">
                    <h3>Computer Lab</h3>
                    <p>Access to modern workstations with specialized software for engineering and design students.</p>
                </div>
            </div>

            <div class="hotspot" id="spot-ref">
                <i class='bx bx-book-bookmark'></i>
                <div class="hotspot-tooltip">
                    <h3>Reference Section</h3>
                    <p>Encyclopedias, academic journals, and archives. A research librarian is available here.</p>
                </div>
            </div>
            
            <div class="hotspot" id="spot-lounge">
                <i class='bx bx-coffee-togo'></i>
                <div class="hotspot-tooltip">
                    <h3>Lounge Area</h3>
                    <p>Relaxed seating area with vending machines. Low conversational volume permitted.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
