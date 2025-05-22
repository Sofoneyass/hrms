
<?php
require_once 'db_connection.php';

// Fetch approved testimonials with user details
$stmt = $conn->prepare("
    SELECT t.testimonial_id, t.content, t.rating, t.created_at, u.full_name, u.profile_image
    FROM testimonials t
    JOIN users u ON t.user_id = u.user_id
    WHERE t.status = 'approved'
    ORDER BY t.created_at DESC
    LIMIT 5
");
if (!$stmt) {
    error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
} else {
    $stmt->execute();
    $testimonials = $stmt->get_result();
}
?>

<section class="testimonial-section" aria-labelledby="testimonial-heading">
    <h2 id="testimonial-heading" class="testimonial-heading">What Our Users Say</h2>
    <?php if ($testimonials && $testimonials->num_rows > 0): ?>
        <div class="swiper-container">
            <div class="swiper-wrapper">
                <?php while ($row = $testimonials->fetch_assoc()): ?>
                    <article class="swiper-slide testimonial-card" aria-labelledby="testimonial-<?= htmlspecialchars($row['testimonial_id']) ?>">
                        <img src="Uploads/<?= htmlspecialchars($row['profile_image'] ?? 'default.jpg') ?>" class="testimonial-avatar" alt="Avatar of <?= htmlspecialchars($row['full_name']) ?>" loading="lazy">
                        <div class="testimonial-content">
                            <h3 id="testimonial-<?= htmlspecialchars($row['testimonial_id']) ?>" class="testimonial-author"><?= htmlspecialchars($row['full_name']) ?></h3>
                            <div class="testimonial-rating" aria-label="Rating: <?= $row['rating'] ?> stars">
                                <?= str_repeat("⭐", $row['rating']) ?>
                            </div>
                            <p class="testimonial-comment"><?= nl2br(htmlspecialchars($row['content'])) ?></p>
                            <time class="testimonial-date" datetime="<?= $row['created_at'] ?>">
                                Posted on <?= date("F j, Y", strtotime($row['created_at'])) ?>
                            </time>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
            <!-- Navigation buttons -->
            <div class="swiper-button-prev" aria-label="Previous testimonial"></div>
            <div class="swiper-button-next" aria-label="Next testimonial"></div>
            <!-- Pagination dots -->
            <div class="swiper-pagination" aria-label="Testimonial pagination"></div>
        </div>
    <?php else: ?>
        <p class="no-testimonials">No testimonials available yet.</p>
    <?php endif; ?>
    <?php if ($stmt) $stmt->close(); ?>
</section>

<style>
    :root {
        --primary: #6dd5fa;
        --secondary: #2980b9;
        --accent: #00b894;
        --text: #333;
        --light: #f8f9fa;
        --dark-bg: #1e293b;
        --dark-text: #e2e8f0;
        --glass-bg: rgba(255, 255, 255, 0.25);
        --glass-dark-bg: rgba(30, 41, 59, 0.3);
        --shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    body {
        background: linear-gradient(135deg, #e0eafc, #cfdef3);
        color: var(--text);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body.dark-mode {
        background: linear-gradient(135deg, #1e293b, #334155);
        color: var(--dark-text);
    }

    .testimonial-section {
        max-width: 1200px;
        margin: 4rem auto;
        padding: 2rem;
    }

    .testimonial-heading {
        text-align: center;
        font-size: 2.2rem;
        font-weight: 700;
        color: var(--secondary);
        background: linear-gradient(to right, var(--primary), var(--secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 2rem;
    }

    body.dark-mode .testimonial-heading {
        color: var(--dark-text);
    }

    .swiper-container {
        position: relative;
        padding: 0 3rem;
    }

    .testimonial-card {
        background: var(--glass-bg);
        backdrop-filter: blur(10px);
        border-radius: 16px;
        padding: 1.5rem;
        display: flex;
        gap: 1rem;
        box-shadow: var(--shadow);
        border: 1px solid rgba(255, 255, 255, 0.18);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    body.dark-mode .testimonial-card {
        background: var(--glass-dark-bg);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .testimonial-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.2);
    }

    .testimonial-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--primary);
    }

    .testimonial-content {
        flex: 1;
    }

    .testimonial-author {
        font-size: 1.2rem;
        font-weight: 600;
        margin: 0;
        color: var(--secondary);
    }

    body.dark-mode .testimonial-author {
        color: var(--dark-text);
    }

    .testimonial-rating {
        color: #f0c14b;
        font-size: 1.1rem;
        margin: 0.5rem 0;
    }

    .testimonial-comment {
        font-size: 1rem;
        margin: 0.5rem 0;
        line-height: 1.6;
    }

    .testimonial-date {
        font-size: 0.85rem;
        color: #6b7280;
    }

    body.dark-mode .testimonial-date {
        color: #9ca3af;
    }

    .no-testimonials {
        text-align: center;
        font-size: 1.1rem;
        color: #6b7280;
    }

    body.dark-mode .no-testimonials {
        color: #9ca3af;
    }

    .swiper-button-prev,
    .swiper-button-next {
        color: var(--primary);
        background: var(--glass-bg);
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    body.dark-mode .swiper-button-prev,
    body.dark-mode .swiper-button-next {
        background: var(--glass-dark-bg);
    }

    .swiper-button-prev:hover,
    .swiper-button-next:hover {
        background: var(--primary);
        color: var(--text);
    }

    .swiper-button-prev::after,
    .swiper-button-next::after {
        font-size: 1.2rem;
    }

    .swiper-pagination-bullet {
        background: var(--primary);
        opacity: 0.5;
    }

    .swiper-pagination-bullet-active {
        opacity: 1;
    }

    @media (max-width: 768px) {
        .swiper-container {
            padding: 0 1rem;
        }

        .testimonial-card {
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 1rem;
        }

        .testimonial-avatar {
            margin-bottom: 0.5rem;
        }

        .testimonial-heading {
            font-size: 1.8rem;
        }
    }

    @media (max-width: 600px) {
        .testimonial-section {
            padding: 1.5rem;
        }

        .testimonial-heading {
            font-size: 1.5rem;
        }

        .testimonial-avatar {
            width: 50px;
            height: 50px;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const swiper = new Swiper('.swiper-container', {
            slidesPerView: 1,
            spaceBetween: 30,
            loop: true,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            breakpoints: {
                768: {
                    slidesPerView: 2,
                },
                1024: {
                    slidesPerView: 3,
                },
            },
        });
    });
</script>