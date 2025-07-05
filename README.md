# 中国银行联行号及银行卡BIN归属行批量查询

本项目是一个用于中国银行联行号和银行卡BIN归属行批量查询的Web工具，支持在线查询和数据导入。适用于银行业务辅助、财务对账、批量验证等场景。

## 功能简介

- **联行号查询**：根据银行名称、联行号、城市、代码等关键词，快速检索全国银行的联行号信息。
- **银行卡BIN归属行查询**：支持单卡号和批量卡号查询，快速识别银行卡对应的银行、卡种、BIN号等详细信息。
- **批量导出**：支持将批量查询结果导出为Excel文件。
- **数据导入脚本**：提供csv数据批量导入MySQL的脚本，方便自建数据库与定期更新数据。

## 目录结构

```
.
├── bank_bin_batch.php        # 批量银行卡归属行查询页面
├── bank_bin_search.php       # 单个银行卡归属行查询页面
├── bin.csv                   # 银行卡BIN数据源
├── config.php                # 数据库及分页配置
├── import_bin.php            # BIN数据导入脚本
├── import_net_bank_code.php  # 联行号数据导入脚本
├── index.php                 # 联行号查询页面
└── net_bank_code.csv         # 联行号数据源（需自备）
```

## 环境要求

- PHP 7.2+
- MySQL 5.7+/8.0+（建议使用utf8mb4字符集）
- Web服务器（Apache/Nginx均可）

## 快速搭建

1. **准备数据库**

   新建数据库，例如 `lian_wsx_tax`，并导入表结构（见下文脚本例子）。

2. **配置数据库**

   编辑 `config.php`，填写你的数据库连接参数：

   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', '你的数据库名');
   define('DB_USER', '你的用户名');
   define('DB_PASS', '你的密码');
   ```

3. **导入数据**

   - 导入银行卡BIN数据：

     ```bash
     php import_bin.php
     ```

   - 导入联行号数据（需准备好 `net_bank_code.csv`）：

     ```bash
     php import_net_bank_code.php
     ```

4. **部署到Web服务器**

   直接将本项目代码上传至网站目录，访问对应的 `.php` 页面即可使用。

## 数据表结构（示例）

**银行卡BIN数据表 bank_bin：**

```sql
CREATE TABLE `bank_bin` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `bank_name` VARCHAR(128) NOT NULL,
  `bank_code` VARCHAR(32),
  `bank_abbr` VARCHAR(32),
  `card_name` VARCHAR(128),
  `card_type` VARCHAR(32),
  `card_length` INT,
  `bin` VARCHAR(16),
  `bin_length` INT,
  INDEX idx_bin (bin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**联行号数据表 bank_codes：**

见 `import_net_bank_code.php` 脚本中的建表语句。

## 使用说明

- 访问 `index.php`：银行联行号查询
- 访问 `bank_bin_search.php`：单卡号或关键词银行卡归属行查询
- 访问 `bank_bin_batch.php`：批量输入卡号，进行批量查询和导出

## 数据来源与免责声明

- 本项目数据来源于网络公开渠道，仅供学习和公益查询使用。
- 数据准确性不作商业保证，请勿用于生产环境的金融结算、反欺诈等关键业务。
- 如需商业合作或数据定制，请联系项目作者。

## 贡献

欢迎提交PR、修复bug、完善数据。如有更全最新数据源，欢迎提供。

---

**公益免费，无需付费。谨防诈骗！**

© [Zhuli.Pro](https://zhuli.pro) | 鲁ICP备2025169043号
