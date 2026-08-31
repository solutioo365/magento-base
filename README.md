# Solutioo Base for Magento 2

Lightweight foundation module for [Solutioo](https://www.solutioo.de) Magento extensions.

If you install any Solutioo Magento 2 module, this package usually comes first: it adds the **Solutioo** entry in the Magento Admin menu, the shared ACL resource, and a small config helper used by our other modules.

Works with **Magento Open Source** and **Adobe Commerce**, including shops running **Hyvä Theme** or the default Luma storefront (Base itself is Admin-only).

## Why this module?

- One place for the Solutioo Admin menu and permissions
- Stable dependency for packages such as [EU Guarantee / GARAN](https://github.com/solutioo365/magento-garan)
- No storefront theme overrides, no heavy framework on top of Magento

## Requirements

- Magento 2.4.x / Adobe Commerce 2.4.x
- PHP 8.1+

## Installation

### Composer (recommended)

```bash
composer config repositories.solutioo composer https://www.solutioo.de/wp-content/packages/
composer require solutioo/module-base
bin/magento module:enable Solutioo_Base
bin/magento setup:upgrade
bin/magento cache:flush
```

Alternatively via GitHub directly:

```bash
composer config repositories.solutioo-base vcs https://github.com/solutioo365/magento-base.git
composer require solutioo/module-base
```

### Manual (`app/code`)

Copy this repository to `app/code/Solutioo/Base`, then:

```bash
bin/magento module:enable Solutioo_Base
bin/magento setup:upgrade
bin/magento cache:flush
```

## Configuration

**Solutioo → Base** (oder Stores → Configuration → Solutioo → Base)

| Setting | Description |
|--------|-------------|
| App-Infos anzeigen | Optional. Zeigt im Admin eine Übersicht zu Solutioo Magento-Modulen, Shop-Connectoren, Versand- und Business-Central-Apps. |
| Anonyme Nutzungsrückmeldung | Optional. Hilft bei Support/Kompatibilität. Übertragen: Hostname, Modul- und Magento-Version. Keine Kunden- oder Bestelldaten. Jederzeit deaktivierbar. |

## Use in your own Solutioo module

`etc/module.xml`:

```xml
<module name="Solutioo_YourModule">
    <sequence>
        <module name="Solutioo_Base"/>
    </sequence>
</module>
```

Menu under Solutioo:

```xml
<add id="Solutioo_YourModule::root"
     title="Your Module"
     module="Solutioo_YourModule"
     sortOrder="20"
     parent="Solutioo_Base::solutioo"
     resource="Solutioo_YourModule::root"/>
```

Config providers can extend `Solutioo\Base\Model\ConfigProviderAbstract`.

## Compatibility

| Area | Support |
|------|---------|
| Magento Admin | Yes |
| Luma storefront | N/A (no frontend output) |
| Hyvä Theme | N/A (no frontend output) |
| Magento 2.4.6 – 2.4.8 | Tested |

## Support

- Website: [www.solutioo.de](https://www.solutioo.de)
- Email: info@solutioo.de
- Issues: use the GitHub issue tracker on this repository

## Licence

OSL-3.0 / AFL-3.0

---

Keywords: Magento 2 module, Adobe Commerce extension, Solutioo Base, Magento Admin menu, Hyvä compatible Magento modules, Magento 2 ACL, open source Magento extension Germany
