-- Virtual Event Management System (VEMS) - Speaker Marketplace Database Schema

CREATE DATABASE IF NOT EXISTS speaker_marketplace;
USE speaker_marketplace;

-- Users Table (Event Organizers)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    organization VARCHAR(200),
    website VARCHAR(255),
    status ENUM('active', 'blocked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Admin Users Table
CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'editor') DEFAULT 'editor',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Speaker Categories/Expertise Table
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Speakers Table (formerly Products)
CREATE TABLE speakers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    title VARCHAR(255),
    bio TEXT,
    expertise TEXT,
    category_id INT,
    hourly_rate DECIMAL(10, 2),
    keynote_rate DECIMAL(10, 2),
    workshop_rate DECIMAL(10, 2),
    panel_rate DECIMAL(10, 2),
    virtual_rate DECIMAL(10, 2),
    location VARCHAR(255),
    languages VARCHAR(255),
    years_experience INT,
    total_events INT DEFAULT 0,
    rating DECIMAL(3, 2) DEFAULT 0,
    total_reviews INT DEFAULT 0,
    availability_status ENUM('available', 'busy', 'unavailable') DEFAULT 'available',
    video_intro_url VARCHAR(255),
    linkedin_url VARCHAR(255),
    twitter_url VARCHAR(255),
    website_url VARCHAR(255),
    status ENUM('active', 'inactive', 'pending') DEFAULT 'pending',
    views INT DEFAULT 0,
    bookings INT DEFAULT 0,
    featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Speaker Photos/Portfolio
CREATE TABLE speaker_photos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    speaker_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    caption VARCHAR(255),
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (speaker_id) REFERENCES speakers(id) ON DELETE CASCADE
);

-- Speaker Expertise Tags
CREATE TABLE speaker_expertise (
    id INT PRIMARY KEY AUTO_INCREMENT,
    speaker_id INT NOT NULL,
    expertise VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (speaker_id) REFERENCES speakers(id) ON DELETE CASCADE
);

-- Event Formats
CREATE TABLE event_formats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Speaker Available Formats
CREATE TABLE speaker_formats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    speaker_id INT NOT NULL,
    format_id INT NOT NULL,
    rate DECIMAL(10, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (speaker_id) REFERENCES speakers(id) ON DELETE CASCADE,
    FOREIGN KEY (format_id) REFERENCES event_formats(id) ON DELETE CASCADE
);

-- Booking Requests (formerly Orders)
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    organizer_id INT NOT NULL,
    booking_number VARCHAR(50) UNIQUE NOT NULL,
    event_name VARCHAR(255) NOT NULL,
    event_date DATE,
    event_time TIME,
    event_duration VARCHAR(50),
    event_type ENUM('virtual', 'physical', 'hybrid') DEFAULT 'virtual',
    event_location VARCHAR(255),
    expected_attendees INT,
    event_description TEXT,
    total_amount DECIMAL(10, 2) NOT NULL,
    discount_amount DECIMAL(10, 2) DEFAULT 0,
    tax_amount DECIMAL(10, 2) DEFAULT 0,
    final_amount DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50),
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    transaction_id VARCHAR(100),
    coupon_code VARCHAR(50),
    booking_status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Booking Items (Speakers booked for event)
CREATE TABLE booking_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    speaker_id INT NOT NULL,
    speaker_name VARCHAR(255) NOT NULL,
    format VARCHAR(100),
    session_title VARCHAR(255),
    session_duration VARCHAR(50),
    rate DECIMAL(10, 2) NOT NULL,
    quantity INT DEFAULT 1,
    special_requirements TEXT,
    speaker_status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    speaker_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (speaker_id) REFERENCES speakers(id) ON DELETE CASCADE
);

-- Speaker Availability Calendar
CREATE TABLE speaker_availability (
    id INT PRIMARY KEY AUTO_INCREMENT,
    speaker_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('available', 'booked', 'blocked') DEFAULT 'available',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (speaker_id) REFERENCES speakers(id) ON DELETE CASCADE
);

-- Booking Cart (temporary booking requests)
CREATE TABLE booking_cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    session_id VARCHAR(100),
    speaker_id INT NOT NULL,
    format VARCHAR(100),
    event_date DATE,
    duration VARCHAR(50),
    special_requirements TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (speaker_id) REFERENCES speakers(id) ON DELETE CASCADE
);

-- Speaker Reviews & Ratings
CREATE TABLE speaker_reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    speaker_id INT NOT NULL,
    organizer_id INT NOT NULL,
    booking_id INT,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_title VARCHAR(255),
    review_text TEXT,
    would_recommend BOOLEAN DEFAULT TRUE,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (speaker_id) REFERENCES speakers(id) ON DELETE CASCADE,
    FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL
);

-- Coupons Table
CREATE TABLE coupons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    type ENUM('flat', 'percentage') NOT NULL,
    value DECIMAL(10, 2) NOT NULL,
    min_purchase DECIMAL(10, 2) DEFAULT 0,
    max_discount DECIMAL(10, 2),
    usage_limit INT,
    used_count INT DEFAULT 0,
    expires_at TIMESTAMP NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Support Tickets Table
CREATE TABLE support_tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    booking_number VARCHAR(50),
    status ENUM('open', 'in_progress', 'closed') DEFAULT 'open',
    admin_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- FAQ Table
CREATE TABLE faqs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    question VARCHAR(255) NOT NULL,
    answer TEXT NOT NULL,
    category ENUM('organizer', 'speaker', 'general') DEFAULT 'general',
    display_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Settings Table
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Password Reset Tokens Table
CREATE TABLE password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(100) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Notifications Table
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    speaker_id INT,
    type VARCHAR(50),
    title VARCHAR(255),
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert Default Admin
INSERT INTO admins (name, email, password, role) VALUES 
('Super Admin', 'admin@speakermarket.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin');
-- Default password: password

-- Insert Default Settings
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'Speaker Marketplace - VEMS'),
('site_email', 'info@speakermarket.com'),
('currency', 'USD'),
('currency_symbol', '$'),
('tax_rate', '10'),
('payment_gateway', 'stripe'),
('stripe_public_key', ''),
('stripe_secret_key', ''),
('razorpay_key_id', ''),
('razorpay_key_secret', ''),
('paypal_client_id', ''),
('paypal_secret', ''),
('booking_commission', '15'),
('platform_fee', '5');

-- Insert Sample Categories (Speaker Expertise)
INSERT INTO categories (name, slug, description, icon) VALUES
('Technology & Innovation', 'technology-innovation', 'Tech experts, AI, Digital Transformation', 'fa-laptop-code'),
('Business & Leadership', 'business-leadership', 'Leadership, Management, Entrepreneurship', 'fa-briefcase'),
('Marketing & Sales', 'marketing-sales', 'Digital Marketing, Sales Strategy, Branding', 'fa-chart-line'),
('Health & Wellness', 'health-wellness', 'Mental Health, Fitness, Work-Life Balance', 'fa-heartbeat'),
('Motivation & Inspiration', 'motivation-inspiration', 'Motivational Speakers, Life Coaches', 'fa-star'),
('Education & Training', 'education-training', 'Corporate Training, Skill Development', 'fa-graduation-cap'),
('Finance & Economics', 'finance-economics', 'Financial Planning, Investment, Economics', 'fa-dollar-sign'),
('Diversity & Inclusion', 'diversity-inclusion', 'DEI, Cultural Awareness, Social Impact', 'fa-users');

-- Insert Event Formats
INSERT INTO event_formats (name, description) VALUES
('Keynote Speech', '30-60 minute main stage presentation'),
('Workshop', 'Interactive 2-4 hour hands-on session'),
('Panel Discussion', 'Moderated discussion with multiple speakers'),
('Fireside Chat', 'Informal conversational interview format'),
('Virtual Webinar', 'Online presentation or training session'),
('Breakout Session', 'Small group focused discussion'),
('Training Session', 'Full or half-day training program'),
('MC/Moderator', 'Event hosting and moderation');

-- Insert Sample Speakers
INSERT INTO speakers (name, slug, title, bio, category_id, keynote_rate, workshop_rate, virtual_rate, location, languages, years_experience, total_events, rating, status, featured) VALUES
('Dr. Sarah Johnson', 'dr-sarah-johnson', 'AI & Digital Transformation Expert', 'Leading expert in artificial intelligence and digital transformation with 15+ years of experience helping Fortune 500 companies innovate.', 1, 5000.00, 8000.00, 3000.00, 'San Francisco, CA', 'English, Spanish', 15, 250, 4.9, 'active', TRUE),
('Michael Chen', 'michael-chen', 'Leadership & Executive Coach', 'Former CEO turned leadership coach, specializing in executive development and organizational transformation.', 2, 4500.00, 7000.00, 2500.00, 'New York, NY', 'English, Mandarin', 20, 180, 4.8, 'active', TRUE),
('Emma Williams', 'emma-williams', 'Digital Marketing Strategist', 'Award-winning marketing expert helping brands grow through innovative digital strategies and social media.', 3, 3500.00, 5500.00, 2000.00, 'London, UK', 'English', 12, 150, 4.7, 'active', FALSE),
('Dr. James Rodriguez', 'dr-james-rodriguez', 'Wellness & Mental Health Advocate', 'Clinical psychologist and wellness expert promoting mental health awareness in corporate environments.', 4, 4000.00, 6500.00, 2200.00, 'Miami, FL', 'English, Spanish', 18, 200, 4.9, 'active', TRUE);

-- Insert Sample FAQs
INSERT INTO faqs (question, answer, category, display_order) VALUES
('How do I book a speaker?', 'Browse our speaker directory, select your preferred speaker, add them to your booking cart, and complete the booking request form with your event details.', 'organizer', 1),
('What payment methods do you accept?', 'We accept all major credit cards, PayPal, and bank transfers for speaker bookings.', 'organizer', 2),
('Can I request multiple speakers for one event?', 'Yes! You can add multiple speakers to your booking cart and submit a single booking request for your entire event.', 'organizer', 3),
('What is your cancellation policy?', 'Cancellations made 30+ days before the event receive a full refund. 15-30 days: 50% refund. Less than 15 days: no refund.', 'organizer', 4),
('How do speakers set their rates?', 'Speakers set their own rates based on format (keynote, workshop, virtual) and can adjust them at any time through their dashboard.', 'speaker', 5),
('How long does it take to get booking confirmations?', 'Speakers typically respond to booking requests within 24-48 hours. You will receive email notifications for all status updates.', 'general', 6);
