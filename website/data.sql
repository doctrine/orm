INSERT INTO `blog_post` (`id`, `created_at`, `updated_at`, `name`, `slug`, `body`) VALUES 
(1, '2007-09-06 11:37:00', '2007-09-06 11:41:06', 'Test Blog Post', 'test-blog-post', 'This is a test blog post!!');

INSERT INTO `sf_guard_user` (`id`, `created_at`, `updated_at`, `username`, `algorithm`, `salt`, `password`, `last_login`, `is_active`, `is_super_admin`) VALUES 
(1, '2007-09-06 11:25:14', '2007-09-06 11:25:36', 'admin', 'sha1', '9dc8c66967e1bfb0e25884958a209796', '9861d585ef106d4eef607a86ad451696a926b5d3', '2007-09-06 11:25:36', 1, 1);