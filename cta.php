<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Your Dream Home | Jigjiga Homes</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2a7f62;
            --primary-dark: #1e3c2b;
            --accent: #f0c14b;
            --text-dark: #333;
            --text-light: #f8f9fa;
            --bg-light: #ffffff;
            --bg-dark: #121212;
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            line-height: 1.6;
            transition: var(--transition);
        }

        body.dark-mode {
            background-color: var(--bg-dark);
            color: var(--text-light);
        }

        /* Header */
        .cta-header {
            background: linear-gradient(135deg, rgba(30, 60, 43, 0.9) 0%, rgba(42, 127, 98, 0.9) 100%), url('jigjiga-cityscape.jpg') center/cover no-repeat;
            height: 80vh;
            min-height: 600px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            padding: 0 20px;
            position: relative;
        }

        .cta-header-content {
            max-width: 800px;
            z-index: 2;
        }

        .cta-title {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .cta-title span {
            color: var(--accent);
        }

        .cta-subtitle {
            font-size: 1.3rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        /* CTA Buttons */
        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 40px;
        }

        .btn {
            padding: 15px 30px;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            display: inline-block;
            font-size: 1.1rem;
        }

        .btn-primary {
            background: var(--accent);
            color: var(--primary-dark);
            border: 2px solid var(--accent);
        }

        .btn-primary:hover {
            background: #e2b33a;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(240, 193, 75, 0.3);
        }

        .btn-secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-3px);
        }

       
        /* Final CTA */
        .final-cta {
            padding: 100px 20px;
            text-align: center;
            background-color: #f8faf9;
        }

        body.dark-mode .final-cta {
            background-color: #1a1a1a;
        }

        .final-cta-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.8rem;
            margin-bottom: 20px;
            color: var(--primary-dark);
        }

        body.dark-mode .final-cta-title {
            color: var(--text-light);
        }

        .final-cta-title span {
            color: var(--primary);
        }

        body.dark-mode .final-cta-title span {
            color: var(--accent);
        }

        .final-cta-subtitle {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto 40px;
            color: #666;
        }

        body.dark-mode .final-cta-subtitle {
            color: #aaa;
        }

      
        /* Responsive */
        @media (max-width: 768px) {
            .cta-title {
                font-size: 2.5rem;
            }
            
            .cta-subtitle {
                font-size: 1.1rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .final-cta-title {
                font-size: 2.2rem;
            }
        }

        @media (max-width: 480px) {
            .cta-title {
                font-size: 2rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                gap: 15px;
            }
            
            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Hero CTA Section -->
    <header class="cta-header">
        <div class="cta-header-content">
            <h1 class="cta-title">Find Your <span>Dream Home</span> in Jigjiga</h1>
            <p class="cta-subtitle">Premium properties with verified listings and local expertise</p>
            
            <div class="cta-buttons">
                <a href="properties.php" class="btn btn-primary">
                    <i class="fas fa-search"></i> Browse Properties
                </a>
                <a href="contact.php" class="btn btn-secondary">
                    <i class="fas fa-headset"></i> Contact Agent
                </a>
            </div>
        </div>
    </header>

    

    

    <!-- Final CTA Section -->
    <section class="final-cta">
        <div class="section-container">
            <h2 class="final-cta-title">Ready to <span>Find Your Home?</span></h2>
            <p class="final-cta-subtitle">Join hundreds of satisfied tenants who found their perfect home through Jigjiga Homes</p>
            
            <div class="cta-buttons">
                <a href="register.php" class="btn btn-primary" style="padding: 15px 40px;">
                    <i class="fas fa-user-plus"></i> Create Account
                </a>
            </div>
        </div>
    </section>

   
</body>
</html>