I’ll map where this app builds and filters the search index, then trace where file content is extracted so we can identify the best integration points for EXIF metadata indexing. I’m starting by locating the indexing pipeline and MIME/type eligibility checks.

Searched for regex `index|Indexer|Provider|Document|canIndex|mime|mimetype|binary|extract` (`**/lib/**`), 200 results

Searched for files matching `**/lib/**/*.php`, 88 matches

I found the core indexing framework classes; next I’ll trace the exact call flow from “queue document” to “fill content” and confirm where MIME/binary decisions happen versus provider-specific logic.

Read [](file:///home/martin/git/fulltextsearch/lib/Service/IndexService.php#1-1), lines 1 to 320

Read [](file:///home/martin/git/fulltextsearch/lib/Service/ProviderService.php)

Read [](file:///home/martin/git/fulltextsearch/lib/Service/CollectionService.php)

I’ve confirmed the provider-driven flow and now need the lower half of the indexing service, where the actual “indexable or not” filtering is implemented.

Read [](file:///home/martin/git/fulltextsearch/lib/Service/IndexService.php#320-320), lines 320 to 640

Searched for regex `fulltextsearch_files|files provider|content provider|extractor|exif|metadata|mimetype|mime` (`**/{README.md,docs/**,lib/**,appinfo/**,composer.json}`), 3 results

Searched for regex `fillIndexDocument|generateIndexableDocuments|updateDocument\(|isDocumentUpToDate\(` (`**/lib/**`), 18 results

The search confirms this repository is mostly the orchestration layer; I’ll now read the cron/CLI indexing entry points plus README docs to pinpoint where provider-specific file decisions are expected to live.

Read [](file:///home/martin/git/fulltextsearch/lib/Cron/Index.php)

Read [](file:///home/martin/git/fulltextsearch/lib/Command/Index.php#1-1), lines 1 to 320

Read [](file:///home/martin/git/fulltextsearch/lib/Command/Live.php#1-1), lines 1 to 340

Read [](file:///home/martin/git/fulltextsearch/README.md)

Read [](file:///home/martin/git/fulltextsearch/lib/Provider/TestProvider.php)

Searched for regex `mimetype|mime|binary|isBinary|exif|iptc|xmp|image` (`**/lib/**`), 2 results

Quick update: there are effectively no MIME/binary checks in this core app’s PHP code, which strongly indicates those checks are implemented in provider apps. I’ll pull the app wiring and docs pages to show exactly where provider extensions plug in.

Read [](file:///home/martin/git/fulltextsearch/lib/AppInfo/Application.php)

Read [](file:///home/martin/git/fulltextsearch/docs/index.md)

Read [](file:///home/martin/git/fulltextsearch/docs/configuration.md)

Read [](file:///home/martin/git/fulltextsearch/docs/commands.md)

Read [](file:///home/martin/git/fulltextsearch/lib/Model/Index.php#1-1), lines 1 to 340

Read [](file:///home/martin/git/fulltextsearch/lib/ACommandBase.php)

Read [](file:///home/martin/git/fulltextsearch/lib/Model/IndexOptions.php)

Read [](file:///home/martin/git/fulltextsearch/lib/Command/Index.php#320-320), lines 320 to 640

Read [](file:///home/martin/git/fulltextsearch/lib/Service/SearchService.php)

Read [](file:///home/martin/git/fulltextsearch/lib/Search/UnifiedSearchProvider.php)

Read [](file:///home/martin/git/fulltextsearch/lib/Controller/CollectionController.php#140-140), lines 140 to 190

I now have enough context to outline extension points. I’ll quickly inspect the document debug commands, since they’re useful when validating EXIF indexing for a single file/document.

Read [](file:///home/martin/git/fulltextsearch/lib/Command/DocumentProvider.php)

Read [](file:///home/martin/git/fulltextsearch/lib/Command/DocumentIndex.php)

Read [](file:///home/martin/git/fulltextsearch/lib/Command/DocumentPlatform.php)

Read [](file:///home/martin/git/fulltextsearch/lib/Command/DocumentStatus.php)

Read [](file:///home/martin/git/fulltextsearch/composer.json)

Read [](file:///home/martin/git/fulltextsearch/appinfo/info.xml)

I’m adding one more pass over the document model usage so I can suggest whether EXIF should go into content, tags, or metatags in this architecture.

Searched for regex `getMetaTags|setMetaTags|getTags|setTags|setParts|getParts|setContent` (`**/lib/**`), 20 results

Read [](file:///home/martin/git/fulltextsearch/lib/Service/TestService.php)

Read [](file:///home/martin/git/fulltextsearch/lib/Model/SearchRequest.php#1-1), lines 1 to 340

Read [](file:///home/martin/git/fulltextsearch/lib/Model/SearchRequest.php#340-340), lines 340 to 560

Read [](file:///home/martin/git/fulltextsearch/lib/Command/Search.php)

I’ve mapped the indexing pipeline end-to-end: this app orchestrates providers/platforms, but MIME/binary/EXIF extraction logic is not implemented in this repository’s core path. I’ll now summarize the concrete files and methods you’d modify, and which ones are just orchestration you probably should not change.Your assumption is correct about a decision point, but in this repository it is only a thin gate. The real “is this file indexable / how to extract text or metadata” logic is expected to be in a provider app (typically fulltextsearch_files), not in core fulltextsearch.

Interesting areas in this app

1. Provider loading and extension point
- Providers are discovered from installed apps and must implement IFullTextSearchProvider in ProviderService.php and ProviderService.php.
- This is where you see that extraction is delegated to providers, not handled centrally.

2. Index build pipeline (main orchestration)
- Main flow: generate candidates → compare with existing index → fill document → send to platform.
- Core methods:
  - IndexService.php
  - IndexService.php
  - IndexService.php
  - provider fill hook at IndexService.php
- This is the best place to understand lifecycle, retries, statuses, and where your provider is called.

3. “Can this be indexed?” gate in core
- There is a filter method in IndexService.php, but it only checks access/index-ignore and sets owner id.
- No MIME/binary/EXIF logic is present there.

4. Re-index queue / cron processing
- Reindex processing path is in Index.php and calls IndexService.php, which again delegates to provider updateDocument.
- Useful to know for background updates after metadata support is added.

5. Search orchestration
- Search request and provider/platform delegation in SearchService.php and SearchService.php.
- Core does not build field-level query mappings itself.

6. Data model fields that can carry metadata
- Document view exposes tags/metatags/parts/content in CollectionController.php.
- SearchRequest supports tags/metatags/parts in SearchRequest.php, SearchRequest.php, SearchRequest.php.

What this means for EXIF support

- Primary implementation target is the file provider app, not this core app.
- In provider logic, likely methods to extend are:
  - generateIndexableDocuments (candidate selection, MIME/binary decisions)
  - fillIndexDocument or updateDocument (extract EXIF and attach to content/tags/metatags)
- In this repo, you likely do not need major changes unless you want extra generic hooks/options.

Likely second required area (outside this repo)

- Platform app mapping/query support (for example fulltextsearch_elasticsearch) may need updates so EXIF fields are indexed/searched as intended.
- If EXIF is appended to document content, platform changes may be minimal.
- If EXIF goes into structured metatags/fields, platform mapping and query parsing usually need explicit support.

Good starting workflow

1. Keep this app unchanged initially; use it as orchestration reference.
2. Implement EXIF extraction and MIME/binary gating in the files provider app.
3. Verify with diagnostics commands in this app:
- fulltextsearch:document:provider path in DocumentProvider.php
- fulltextsearch:document:index path in DocumentIndex.php
- fulltextsearch:document:platform path in DocumentPlatform.php

If you want, I can next help you map the exact files/methods in fulltextsearch_files where EXIF extraction should be added, and suggest whether to store EXIF in content vs metatags for best search behavior.
