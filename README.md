# 品質評価システム

## 概要

3D都市モデルの品質管理支援のための品質評価システム（Webポータル）です。

本ソフトウェアは、国土交通省の[Project PLATEAU](https://www.mlit.go.jp/plateau/)で開発され、3D都市モデルの品質管理支援に用いたWebポータルシステムです。対応データ形式は、内閣府地方創生推進事務局が定めた[i-都市再生技術仕様案 v1.4 (iｰUR 1.4)](https://www.chisou.go.jp/tiiki/toshisaisei/itoshisaisei/iur/)に従い拡張された[OGC CityGML 2.0](https://www.ogc.org/standards/citygml)形式です。


**ただし、i-UR1.4は2021年9月にi-UR1.5に改定されました。これに伴い、URLが変更されました。**

そのため、本ソフトウェアの利用にあたり、3D都市モデル（CityGML形式）及びソフトウェアに記述された旧URLを、新しいURLに更新する必要があります。

具体的には以下の手順に従い、更新してください。

## 1．3D都市モデル（CityGML形式）に記述されたURLの更新

3D都市モデルのファイル内の名前空間とschemaLocationに記載されている旧URLを新しいURLに更新（テキスト置換）してください。

対象となる記述を下表に示します。

- 名前空間

|旧URL（i-UR1.4のURL）|新しいURL（i-UR1.5のURL）|
| - | - |
|`http://www.kantei.go.jp/jp/singi/tiiki/toshisaisei/itoshisaisei/iur/uro/1.4`|https://www.chisou.go.jp/tiiki/toshisaisei/itoshisaisei/iur/uro/1.5|
|`http://www.kantei.go.jp/jp/singi/tiiki/toshisaisei/itoshisaisei/iur/urf/1.4`|https://www.chisou.go.jp/tiiki/toshisaisei/itoshisaisei/iur/urf/1.5|
|`http://www.kantei.go.jp/jp/singi/tiiki/toshisaisei/itoshisaisei/iur/urg/1.4`|https://www.chisou.go.jp/tiiki/toshisaisei/itoshisaisei/iur/urg/1.5|
|`http://www.kantei.go.jp/jp/singi/tiiki/toshisaisei/itoshisaisei/iur/urt/1.4`|https://www.chisou.go.jp/tiiki/toshisaisei/itoshisaisei/iur/urt/1.5|

- schemaLocation

|旧URL（i-UR1.4のURL）|新しいURL（i-UR1.5のURL）|
| - | - |
|`http://www.kantei.go.jp/jp/singi/tiiki/toshisaisei/itoshisaisei/iur/schemas/uro/1.4/urbanObject.xsd`|https://www.chisou.go.jp/tiiki/toshisaisei/itoshisaisei/iur/schemas/uro/1.5/urbanObject.xsd|
|`http://www.kantei.go.jp/jp/singi/tiiki/toshisaisei/itoshisaisei/iur/schemas/urf/1.4/urbanFunction.xsd`|https://www.chisou.go.jp/tiiki/toshisaisei/itoshisaisei/iur/schemas/uro/1.5/urbanFunction.xsd|
|`http://www.kantei.go.jp/jp/singi/tiiki/toshisaisei/itoshisaisei/iur/schemas/urg/1.4/statisticalGrid.xsd`|https://www.chisou.go.jp/tiiki/toshisaisei/itoshisaisei/iur/schemas/urg/1.5/statisticalGrid.xsd|
|`http://www.kantei.go.jp/jp/singi/tiiki/toshisaisei/itoshisaisei/iur/schemas/urg/1.4/publicTransit.xsd`|https://www.chisou.go.jp/tiiki/toshisaisei/itoshisaisei/iur/schemas/urg/1.5/publicTransit.xsd|

## 2．ソフトウェアに記述されたURL及びバージョンの更新

ソフトウェアに含まれる、以下の2つのファイルに記載された名前空間を新しいURLに更新（テキスト置換）してください。

- 対象ファイル（8ファイル）

|ファイルが格納されているフォルダ|ファイル名|
| - | - |
|[DBServer/validateConfig/](https://github.com/Project-PLATEAU/CityGML-evaluation-system/tree/main/DBServer/validateConfig)|project.xml|
|[APServer/bat/template/](https://github.com/Project-PLATEAU/CityGML-evaluation-system/tree/main/APServer/bat/template)|<p>template\_2d.xml</p><p>template\_3d\_LOD1.xml</p><p>template\_3d\_LOD2.xml</p><p>template\_3d\_LOD2\_Surface.xml</p><p>template\_3d\_LOD3.xml</p><p>template\_3d\_LOD4.xml</p><p>template\_3d\_LODALL.xml</p>|


対象となる記述（名前空間及びschemaLocation）

- 名前空間

|旧URL（i-UR1.4のURL）|新しいURL（i-UR1.5のURL）|
| - | - |
|`http://www.kantei.go.jp/jp/singi/tiiki/toshisaisei/itoshisaisei/iur/uro/1.4`|https://www.chisou.go.jp/tiiki/toshisaisei/itoshisaisei/iur/uro/1.5|
|`http://www.kantei.go.jp/jp/singi/tiiki/toshisaisei/itoshisaisei/iur/urf/1.4`|https://www.chisou.go.jp/tiiki/toshisaisei/itoshisaisei/iur/urf/1.5|
|`http://www.kantei.go.jp/jp/singi/tiiki/toshisaisei/itoshisaisei/iur/urg/1.4`|https://www.chisou.go.jp/tiiki/toshisaisei/itoshisaisei/iur/urg/1.5|
|`http://www.kantei.go.jp/jp/singi/tiiki/toshisaisei/itoshisaisei/iur/urt/1.4`|https://www.chisou.go.jp/tiiki/toshisaisei/itoshisaisei/iur/urt/1.5|

- schemaLocation

|旧URL（i-UR1.4のURL）|新しいURL（i-UR1.5のURL）|
| - | - |
|`http://www.kantei.go.jp/jp/singi/tiiki/toshisaisei/itoshisaisei/iur/schemas/uro/1.4/urbanObject.xsd`|https://www.chisou.go.jp/tiiki/toshisaisei/itoshisaisei/iur/schemas/uro/1.5/urbanObject.xsd|
|`http://www.kantei.go.jp/jp/singi/tiiki/toshisaisei/itoshisaisei/iur/schemas/urf/1.4/urbanFunction.xsd`|https://www.chisou.go.jp/tiiki/toshisaisei/itoshisaisei/iur/schemas/uro/1.5/urbanFunction.xsd|
|`http://www.kantei.go.jp/jp/singi/tiiki/toshisaisei/itoshisaisei/iur/schemas/urg/1.4/statisticalGrid.xsd`|https://www.chisou.go.jp/tiiki/toshisaisei/itoshisaisei/iur/schemas/urg/1.5/statisticalGrid.xsd|
|`http://www.kantei.go.jp/jp/singi/tiiki/toshisaisei/itoshisaisei/iur/schemas/urg/1.4/publicTransit.xsd`|https://www.chisou.go.jp/tiiki/toshisaisei/itoshisaisei/iur/schemas/urg/1.5/publicTransit.xsd|

なお、本ソフトウェアの動作には前提ソフトウェア（商用ソフトウェア）が必要で、単独では利用できません。Webポータルシステム構築の実装例として参考にして下さい。

ポータルから呼び出すCityGMLデータ検証機能の一部については、別リポジトリで提供します。

機能一覧

* データ登録、管理
* 書式・概念一貫性検証機能: https://github.com/Project-PLATEAU/CityGML-validation-function
* 位相一貫性検証機能: https://github.com/Project-PLATEAU/CityGML-geometry-validator
* 位置正確度検証
* ファイル配信
* 掲示板

## 動作環境、前提ソフトウェア

動作環境

* Windows (バッチファイル及びPowerShellスクリプト利用、Windows Server 2016 Datacenterで動作確認)

前提ソフトウェア

* Project PLATEAU関連ソフトウェア：[位相一貫性検証機能](https://github.com/Project-PLATEAU/CityGML-geometry-validator)
* 商用ソフトウェア：[VC Publisher](https://vc.systems/en/products/vc-publisher/)、[VC Map](https://vc.systems/en/products/vc-map/)、[VC Database](https://vc.systems/en/products/vc-database/)、[FME Desktop](https://www.safe.com/fme/fme-desktop/)
* OSS：Word Press 5.5、bbPress、PostgreSQL、PostGIS、MySQL Server、PHP、HTTPサーバ（Apache HTTP Serverなど）

## 利用方法

1. 上記の前提ソフトウェアをインストールします。
1. 本レポジトリの一式をダウンロードしたものをWebサーバ、APサーバ、DBサーバに配置します。WebサーバではWordPress 5.5のディレクトリを上書きします。
1. [位相一貫性検証機能](https://github.com/Project-PLATEAU/CityGML-geometry-validator)からダウンロードしたFME Workspace (*.fmw) をAPServer/batフォルダに配置します。
1. ソース中の`*****`文字列を環境に合わせて置換します。
1. DBServerフォルダ下のPG_CREATE_STATUS_TABLE.sqlをPostgreSQLのデータベースにSQLとして実行します。
1. Webサーバの wp-login.php を開きます。

## ライセンス

Copyright (C) 2021 Hitachi, Ltd.

本ソフトウェアではWordPressを利用させて頂いております。WordPress本体のライセンスに従い、本ソフトウェアも[GNU General Public License v2.0](LICENSE)を適用します。

    WordPress - Web publishing software

    Copyright 2011-2021 by the contributors

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

## 注意事項

* 本レポジトリは参考資料として提供しているものです。動作保証は行っておりません。
* 予告なく変更・削除する可能性があります。
* 本レポジトリの利用により生じた損失及び損害等について、国土交通省及び著作権者はいかなる責任も負わないものとします。
* WordPressは変更したソースのみを含みます。変更箇所には「Project "PLATEAU"」とコメントを記載しています。

## 参考資料

* モデル自動生成システム: https://github.com/Project-PLATEAU/CityGML-production-system
* 位相一貫性検証機能: https://github.com/Project-PLATEAU/CityGML-geometry-validator
* 書式・概念一貫性検証機能: https://github.com/Project-PLATEAU/CityGML-validation-function
