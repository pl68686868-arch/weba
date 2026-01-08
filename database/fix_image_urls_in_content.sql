-- SQL Migration: Fix duplicate URLs in post content
-- Vấn đề: Content có <img src="https://duongtraminhdoan.com/assets/uploads/...">
-- Giải pháp: Replace về relative path <img src="/assets/uploads/...">

-- Update all posts content to use relative paths instead of absolute URLs
UPDATE posts 
SET content = REPLACE(content, 'https://duongtraminhdoan.com/assets/uploads/', '/assets/uploads/')
WHERE content LIKE '%https://duongtraminhdoan.com/assets/uploads/%';

-- If there are any http:// (non-SSL) URLs
UPDATE posts 
SET content = REPLACE(content, 'http://duongtraminhdoan.com/assets/uploads/', '/assets/uploads/')
WHERE content LIKE '%http://duongtraminhdoan.com/assets/uploads/%';

-- Verify the changes
SELECT id, title, 
       CASE 
           WHEN content LIKE '%https://duongtraminhdoan.com/assets/uploads/%' THEN 'Still has absolute URL'
           WHEN content LIKE '%/assets/uploads/%' THEN 'Fixed to relative'
           ELSE 'No images'
       END as status
FROM posts;
