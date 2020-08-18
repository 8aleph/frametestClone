# Create default core users
INSERT INTO 
    master_example
(
    created,
    code,
    description
)
VALUES
(NOW(), 'SEEDEX', 'Seed example');
