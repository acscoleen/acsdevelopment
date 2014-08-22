È†÷S<?php exit; ?>a:6:{s:10:"last_error";s:0:"";s:10:"last_query";s:359:"
					SELECT max(meta_value + 0)
					FROM o5gbc_posts
					LEFT JOIN o5gbc_postmeta ON o5gbc_posts.ID = o5gbc_postmeta.post_id
					WHERE meta_key ='_price'
					AND (
						o5gbc_posts.ID IN (8641,4161,1581,121,101)
						OR (
							o5gbc_posts.post_parent IN (8641,4161,1581,121,101)
							AND o5gbc_posts.post_parent != 0
						)
					)
				";s:11:"last_result";a:1:{i:0;O:8:"stdClass":1:{s:19:"max(meta_value + 0)";s:1:"3";}}s:8:"col_info";a:1:{i:0;O:8:"stdClass":13:{s:4:"name";s:19:"max(meta_value + 0)";s:5:"table";s:0:"";s:3:"def";s:0:"";s:10:"max_length";i:1;s:8:"not_null";i:0;s:11:"primary_key";i:0;s:12:"multiple_key";i:0;s:10:"unique_key";i:0;s:7:"numeric";i:1;s:4:"blob";i:0;s:4:"type";s:4:"real";s:8:"unsigned";i:0;s:8:"zerofill";i:0;}}s:8:"num_rows";i:1;s:10:"return_val";i:1;}