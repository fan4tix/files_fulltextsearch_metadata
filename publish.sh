#!/bin/bash
cd ..
tar --exclude=".*" -czvf files_fulltextsearch_metadata_1.0.0.tar.gz files_fulltextsearch_metadata/
openssl dgst -sha512 -sign ~/.nextcloud/certificates/files_fulltextsearch_metadata.key files_fulltextsearch_metadata_1.0.0.tar.gz | openssl base64 >> signature.txt