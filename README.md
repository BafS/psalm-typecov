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

## Screenshoot

<center>
    <img src="https://i.imgur.com/v9l5IQN.png" />
</center>
