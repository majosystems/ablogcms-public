#a-blog cms 公開ソースコード

配布パッケージで暗号化されて提供されているソースコードの一部を公開しています。モジュールやライブラリを独自に開発したり、一部の挙動を変更・修正したりする際の参考としてご利用ください。

##公開中のファイル

現在のソースコードはv1.5.2時点で、以下のファイルが公開されています。

###基本ライブラリ

+ php/DB.php
+ php/Field.php
+ php/Mail.php
+ php/SQL.php
+ php/Template.php

###a-blog cms 依存ライブラリ

+ php/ACMS/User以下
+ php/ACMS/GET以下（AdminとShopを除く）
+ php/ACMS/Services以下
+ php/ACMS/Services.php
+ php/ACMS/Corrector.php
+ php/ACMS/Filter.php
+ php/ACMS/GET.php
+ php/ACMS/RAM.php
+ php/ACMS/Validator.php

##お願い

これらのソースコードを元に、モジュールなどのプログラムを新規に開発する場合は、独自のユニークな命名（プリフィックスを付けるなど）をしてください。これは、ユーザーサポート等のシーンで、アップルップルが開発したソフトウェアと、それ以外で開発されたソフトウェアを明確に区別するためです。

##利用条件

これらのソースコードの利用について*a-blog cmsと関連した用途である限り*は、複製・編集・再配布および販売することを無償で許可しています。

ただし、オリジナルの著作権表示を残すこと、利用許諾を同梱することをお願いしています。その他、詳細な条件は LICENSE をご覧ください。

利用許諾についてのご意見や、その他ご不明な点・疑問点などがあれば、 info@appleple.com までぜひお問い合わせください。

##関連情報

+  [a-blog cms API Documentation](http://www.a-blogcms.jp/api-docs/ "Generated Documentation")