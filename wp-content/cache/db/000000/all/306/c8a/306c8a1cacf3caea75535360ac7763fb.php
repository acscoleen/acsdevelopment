穤S<?php exit; ?>a:6:{s:10:"last_error";s:0:"";s:10:"last_query";s:361:"
					SELECT max(meta_value + 0)
					FROM o5gbc_posts
					LEFT JOIN o5gbc_postmeta ON o5gbc_posts.ID = o5gbc_postmeta.post_id
					WHERE meta_key ='_price'
					AND (
						o5gbc_posts.ID IN (2991,2971,2521,2351,1701,1681)
						OR (
							o5gbc_posts.post_parent IN (2991,2971,2521,2351,1701,1681)
							AND o5gbc_posts.post_parent != 0
						)
					)
				";s:11:"last_result";a:1:{i:0;O:8:"stdClass":1:{s:19:"max(meta_value + 0)";s:4:"5.78";}}s:8:"col_info";a:1:{i:0;O:8:"stdClass":13:{s:4:"name";s:19:"max(meta_value + 0)";s:7:"orgname";s:0:"";s:5:"table";s:0:"";s:8:"orgtable";s:0:"";s:3:"def";s:0:"";s:2:"db";s:0:"";s:7:"catalog";s:3:"def";s:10:"max_length";i:4;s:6:"length";i:23;s:9:"charsetnr";i:63;s:5:"flags";i:32896;s:4:"type";i:5;s:8:"decimals";i:31;}}s:8:"num_rows";i:1;s:10:"return_val";i:1;}