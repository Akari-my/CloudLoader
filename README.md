<img width="2048" height="380" alt="minecraft_title (2)" src="https://github.com/user-attachments/assets/87e8c7a8-40f7-476e-8488-751d5f50b0c1" />

<p align="center">
  CloudLoader is a modular plugin loader for PocketMine-MP 5 that lets you organize plugins in a custom folder structure and load them automatically with dependency-aware ordering.
</p>

---

## Features
- Recursive module scanning inside a configurable directory
- Loads plugins from nested folders (groups/categories)
- Dependency-aware enable order:
  - `depend`
  - `softdepend`
  - `loadbefore` (handled as an ordering constraint when both modules are present)
- Staging system (symlink/copy) to make PocketMine load modules correctly
- Configurable logging
- `/cloudloader reload` command

---

## Requirements

- **PocketMine-MP 5.0+**
- **DevTools**
  - CloudLoader uses **DevTools FolderPluginLoader** to read plugin descriptions from plugin folders.

---

## Installation

1. Install **DevTools**:
   - Put `DevTools.phar` into your server `plugins/` folder.
   - Start the server once and confirm DevTools is enabled.

2. Install **CloudLoader**:
   - Put the CloudLoader folder into:
     ```
     plugins/CloudLoader/
     ```
   - Start the server once to generate the config.

---

## Folder Structure
CloudLoader loads modules from:
```
plugin_data/CloudLoader/modules/
```

You can place plugins directly inside `modules/` or group them into subfolders.  
CloudLoader scans recursively, so both are valid.

---

### Flat structure example
```
plugin_data/CloudLoader/modules/
├── PurePerms/
│ ├── plugin.yml
│ └── src/
├── PureChat/
│ ├── plugin.yml
│ └── src/
└── EconomyAPI/
├── plugin.yml
└── src/
```

---

### Grouped structure example

```
plugin_data/CloudLoader/modules/
├── ranks/
│ ├── PurePerms/
│ │ ├── plugin.yml
│ │ └── src/
│ └── PureChat/
│ ├── plugin.yml
│ └── src/
└── economy/
└── EconomyAPI/
├── plugin.yml
└── src/
```

---

## Module Rules

Each module **must** be a plugin folder containing:

- `plugin.yml`
- `src/`

CloudLoader does **not** load `.phar` plugins.

Avoid duplicate `name:` values: if two modules have the same name in `plugin.yml`, one will be rejected as duplicate.

---

## Configuration

Config file path:
```
plugin_data/CloudLoader/config.yml
```
Example:

```yml
loader:
  dir: modules
  mode: delayed

staging:
  cleanup: false

logs:
  errors: true
  scan: true
  load_order: true
  modules_loaded: true
  skipped: true
```

---

## ⭐ Contribute

- Found a bug? Open an issue
- Pull requests welcome
- Star the repo if you like the project!
