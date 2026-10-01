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

### Minimum coverage per file

Add `minFileCoverage` to list the files whose type coverage is below a percentage and to make Psalm exit with code 2 (the code it uses for errors), for example in CI:

```xml
        <pluginClass class="BafS\PsalmTypecov\TypeCoverage">
            <minFileCoverage value="80" />
        </pluginClass>
```

It can be combined with a report or used alone.

Note: If you want to always scan all the files, you need to use the `--no-cache` flag.

## Screenshoot

<center>
    <img src="https://i.imgur.com/v9l5IQN.png" />
</center>
