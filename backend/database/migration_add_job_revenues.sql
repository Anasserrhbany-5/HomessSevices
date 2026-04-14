USE home_services;
CREATE TABLE IF NOT EXISTS job_revenues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    worker_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    platform_revenue DECIMAL(10, 2) NOT NULL,
    worker_revenue DECIMAL(10, 2) NOT NULL,
    platform_fee_percentage DECIMAL(5, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id),
    FOREIGN KEY (worker_id) REFERENCES users(id)
);