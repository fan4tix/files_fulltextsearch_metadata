Docker smoke workflow

One command:

make smoke-docker

What it does:

1. Starts/rebuilds the docker-compose stack.
	Note: it pre-cleans nextcloud/elasticsearch containers to avoid the docker-compose v1 ContainerConfig recreate bug.
2. Waits for OCC readiness.
3. Installs/enables fulltextsearch apps and this app.
4. Configures FullTextSearch to use:
	OCA\\FullTextSearch_Elasticsearch\\Platform\\ElasticSearchPlatform
5. Configures Elasticsearch host and index.
6. Copies EXIF fixture file from ../nextcloud-metadata/tests/files/canon.jpg to admin files as sample1.jpg.
7. Scans that file and runs targeted indexing for user admin and provider files.
8. Executes search assertions for canon and eos and verifies EXIF excerpts are present.

Direct script invocation:

./scripts/smoke-docker.sh