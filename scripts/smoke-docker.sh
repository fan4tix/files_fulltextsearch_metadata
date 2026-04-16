#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
FIXTURE_PATH="${PROJECT_DIR}/../nextcloud-metadata/tests/files/canon.jpg"
SAMPLE_NAME="sample1.jpg"
MEDIA_FIXTURE_DIR="${PROJECT_DIR}/../tmp"

if [[ ! -f "${FIXTURE_PATH}" ]]; then
	echo "Fixture not found: ${FIXTURE_PATH}" >&2
	exit 1
fi

shopt -s nullglob
media_fixtures=("${MEDIA_FIXTURE_DIR}"/sample1*)
shopt -u nullglob
if [[ ${#media_fixtures[@]} -eq 0 ]]; then
	echo "No media fixtures found in ${MEDIA_FIXTURE_DIR}/sample1*" >&2
	exit 1
fi

cd "${PROJECT_DIR}"

search_with_retry() {
	local query="$1"
	local attempts="${2:-20}"
	local sleep_seconds="${3:-2}"
	local output=""

	for ((i=1; i<=attempts; i++)); do
		output="$(docker-compose exec -T nextcloud sh -lc "php occ fulltextsearch:search admin ${query}" || true)"
		if grep -qi "sample1\|${SAMPLE_NAME}\|parts.exif" <<<"${output}"; then
			echo "${output}"
			return 0
		fi
		sleep "${sleep_seconds}"
	done

	echo "${output}"
	return 1
}

echo "[1/10] Starting containers"
docker-compose rm -sf nextcloud elasticsearch >/dev/null 2>&1 || true
docker-compose up -d --build

echo "[2/10] Waiting for Nextcloud OCC"
until docker-compose exec -T nextcloud sh -lc 'php occ status >/dev/null 2>&1'; do
	sleep 3
done

echo "[3/10] Installing required apps"
docker-compose exec -T nextcloud sh -lc '
	php occ app:install fulltextsearch || true
	php occ app:install files_fulltextsearch || true
	php occ app:install fulltextsearch_elasticsearch || true
	php occ app:enable files_fulltextsearch_metadata
'

echo "[4/10] Configuring FullTextSearch + Elasticsearch"
docker-compose exec -T nextcloud sh -lc '
	php occ fulltextsearch:configure "{\"search_platform\":\"OCA\\\\FullTextSearch_Elasticsearch\\\\Platform\\\\ElasticSearchPlatform\"}"
	php occ fulltextsearch_elasticsearch:configure "{\"elastic_host\":\"http://elasticsearch:9200\",\"elastic_index\":\"nextcloud\",\"elastic_logger_enabled\":false}"
	php occ config:app:set files_fulltextsearch_metadata exif_format_audio --value="1"
	php occ config:app:set files_fulltextsearch_metadata exif_format_video --value="1"
'

echo "[5/10] Copying EXIF and media fixtures into admin files"
container_id="$(docker-compose ps -q nextcloud)"
docker cp "${FIXTURE_PATH}" "${container_id}:/var/www/html/data/admin/files/${SAMPLE_NAME}"
for media_fixture in "${media_fixtures[@]}"; do
	media_name="$(basename "${media_fixture}")"
	docker cp "${media_fixture}" "${container_id}:/var/www/html/data/admin/files/${media_name}"
done
docker-compose exec -T nextcloud sh -lc "chown www-data:www-data /var/www/html/data/admin/files/${SAMPLE_NAME}"
docker-compose exec -T nextcloud sh -lc "chown www-data:www-data /var/www/html/data/admin/files/sample1*"

echo "[6/10] Scanning files into file cache"
docker-compose exec -T nextcloud sh -lc "php occ files:scan --path=\"admin/files\""

echo "[7/10] Resetting stale runner + indexing admin/files"
docker-compose exec -T nextcloud sh -lc '
	php occ fulltextsearch:stop || true
	php occ fulltextsearch:index --no-readline "{\"user\":\"admin\",\"provider\":\"files\"}"
'

echo "Waiting for indexed data to become searchable"
sleep 3

echo "[8/10] Running EXIF search assertions"
canon_output="$(search_with_retry canon)"
eos_output="$(search_with_retry eos)"

if ! grep -q "title: ${SAMPLE_NAME}" <<<"${canon_output}"; then
	echo "Assertion failed: canon search did not return ${SAMPLE_NAME}" >&2
	exit 1
fi

if ! grep -q "source: parts.exif" <<<"${canon_output}"; then
	echo "Assertion failed: canon search did not include EXIF excerpt" >&2
	exit 1
fi

if ! grep -q "title: ${SAMPLE_NAME}" <<<"${eos_output}"; then
	echo "Assertion failed: eos search did not return ${SAMPLE_NAME}" >&2
	exit 1
fi

if ! grep -q "source: parts.exif" <<<"${eos_output}"; then
	echo "Assertion failed: eos search did not include EXIF excerpt" >&2
	exit 1
fi

echo "[9/10] Running media metadata search assertions"
copilot_output="$(search_with_retry copilot)"
year_output="$(search_with_retry 2026)"

if ! grep -q "sample1" <<<"${copilot_output}"; then
	echo "Assertion failed: copilot search did not return sample1 media files" >&2
	echo "---- copilot output ----" >&2
	echo "${copilot_output}" >&2
	exit 1
fi

if ! grep -q "source: parts.exif" <<<"${copilot_output}"; then
	echo "Assertion failed: copilot search did not include metadata excerpt source" >&2
	echo "---- copilot output ----" >&2
	echo "${copilot_output}" >&2
	exit 1
fi

if ! grep -q "sample1" <<<"${year_output}"; then
	echo "Assertion failed: 2026 search did not return sample1 media files" >&2
	echo "---- 2026 output ----" >&2
	echo "${year_output}" >&2
	exit 1
fi

echo "[10/10] Smoke test passed"
echo "Search checks passed for queries: canon, eos, copilot, 2026"
