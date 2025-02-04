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

You can run psalm (typically `./vendor/bin/psalm`) and the report will get generated on the fly.

Note: If you want to always scan all the files, you need to use the `--no-cache` flag.

## Screenshoot

<center>
    <img src="https://i.imgur.com/v9l5IQN.png" />
</center>
