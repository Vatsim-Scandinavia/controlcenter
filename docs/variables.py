"""Substitute the release version into documentation content at build time.

The version is read from the release-please manifest, which is the single
source of truth kept up to date by the release automation. Because the docs
are published per release tag, each published version of the site carries the
version it was built from.
"""

import json
import re
from pathlib import Path

from mkdocs.exceptions import PluginError

IMAGE = 'ghcr.io/vatsim-scandinavia/control-center'

VERSION = json.loads(
    (Path(__file__).parent.parent / '.release-please-manifest.json').read_text()
)['.']

MAJOR = VERSION.split('.')[0]

VARIABLES = {
    # The full version, e.g. "7.0.0-beta.4".
    '%%version%%': VERSION,
    # The major version alone, e.g. "7".
    '%%major%%': MAJOR,
    # The container image at its floating major tag, which stays correct for
    # the whole lifetime of a major release line.
    '%%image_tag%%': f'{IMAGE}:v{MAJOR}',
}


def on_page_markdown(markdown: str, *, page, config, files) -> str:
    """Replace ``%%variable%%`` tokens and reject stale or unknown ones."""
    if f'{IMAGE}:' in markdown:
        raise PluginError(
            f'{page.file.src_path}: hardcoded image tag, use %%image_tag%% instead'
        )

    for token, value in VARIABLES.items():
        markdown = markdown.replace(token, value)

    if unknown := re.findall(r'%%\w+%%', markdown):
        raise PluginError(f'{page.file.src_path}: unknown variables {unknown}')

    return markdown
