		while ($larr = pg_fetch_array($run_list)){

			$es = qryEmailSettings();

			$body = $bodydata;

			$send_cc = "";
			$send_bcc = "";

			$smtp_data['signature']=$es['sig'];
			$smtp_data['smtp_from']=$es['fromname'];
			$smtp_data['smtp_reply']=$es['reply'];
			$smtp_data['smtp_host']=$es['smtp_host'];
			$smtp_data['smtp_auth']=$es['smtp_auth'];
			$smtp_data['smtp_user']=$es['smtp_user'];
			$smtp_data['smtp_pass']=$es['smtp_pass'];

			// build msg body
			$body = "$body\n\n$smtp_data[signature]";

			// determine whether or not here is an attachment
			//$has_attachment = is_uploaded_file($attachment["tmp_name"]);
			$has_attachment = false;
			// modify message and create content_type header depending on whether or not an attachment was posted
			if ( $has_attachment == false ) {
				$content_type = "text/html;charset=US-ASCII";
				$transfer_encoding = "8bit";
			} else { // has attachment
				$content_type = "multipart/mixed";

				// create the main body
				$body_text = "Content-Type: text/plain; charset=US-ASCII\n";
				$body_text .= "Content-Transfer-Encoding: base64\n";
				$body_text .= "\n" . chunk_split(base64_encode($body));

				// get the attachment data
				$attachment = Array();
				$attachment["data"] = state($id,$fromdate,$todate,$type);
				$attachment["name"] = "statement.pdf";

				// delete the temporary file

				$attachment["data"] = chunk_split(base64_encode($attachment["data"]));

				$attachment["headers"] = "Content-Type: application/x-pdf; name=\"$attachment[name]\"\n";
				$attachment["headers"] .= "Content-Transfer-Encoding: base64\n";
				$attachment["headers"] .= "Content-Disposition: attachment; filename=\"$attachment[name]\"\n";

				$attachment["data"] = "$attachment[headers]\n$attachment[data]";

				// generate a unique boundary ( md5 of filename + ":=" + filesize )
				$boundary = md5($attachment["name"]) . "=:" . strlen($attachment["data"]);
				$content_type .= "; boundary=\"$boundary\"";

				// put together the body
				$body = "\n--$boundary\n$body_text\n\n--$boundary\n$attachment[data]\n\n--$boundary--\n";
			}

			// build headers
			$headers = array();
			$headers[] = "From: $smtp_data[smtp_from]";
			$headers[] = "To: $larr[emailaddress]";
			$headers[] = "Reply-To: $smtp_data[smtp_reply]";
			$headers[] = "X-Mailer: Cubit Mail";
			$headers[] = "Return-Path: $smtp_data[smtp_reply]";
			$headers[] = "Content-Type: $content_type";
			$headers[] = "cc: $send_cc";
			$headers[] = "bcc: $send_bcc";

			// create the mime header if should
			if ( $has_attachment == TRUE ) {
				$headers[] = "MIME-Version: 1.0";
			}

			// create the header variable (it is done this way, to make management of headers easier, since there
			// may be no tabs and unnecesary whitespace in mail headers)
			//$headers[] = "\n"; // add another new line to finish the headers
			$headers = implode("\n", $headers);

			//return "done";
		        // send the message
			$sendmail = & new clsSMTPMail;
			$OUTPUT = $sendmail->sendMessages($smtp_data["smtp_host"], 25, $smtp_data["smtp_auth"], $smtp_data["smtp_user"], $smtp_data["smtp_pass"],$larr['emailaddress'], $smtp_data["smtp_from"], "$subject", $body, $headers);

		}