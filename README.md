# Psalm-Typecov plugin

Experimental Psalm plugin to have type coverage information

## Install

```
composer req --dev bafs/psalm-plugin-typecov
```

Register the plugin in psalm.xml:
```xml
    <plugins>
        <pluginClass class="BafS\PsalmTypecov\TypeCoverage">
            <htmlReport output="typecov-report.html" />
        </pluginClass>
    </plugins>
```

Use `markdownReport` instead of (or together with) `htmlReport` to get a Markdown table, handy for pull request comments or a GitHub Actions job summary:

```xml
            <markdownReport output="typecov-report.md" />
```

You can run psalm (typically `./vendor/bin/psalm`) and the report will get generated on the fly.

Note: If you want to always scan all the files, you need to use the `--no-cache` flag.

## Development

```
composer install
composer test      # runs Psalm with the plugin on a fixture project
composer psalm     # the plugin analyzes its own code and writes its own coverage report
composer cs-check
```

CI runs the tests and the self-analysis on Psalm 5, 6 and 7.

## Screenshoot

<center>
    <img src="https://i.imgur.com/v9l5IQN.png" />
</center>
