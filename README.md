# CoinAPI

CoinAPI is a legacy PocketMine-MP coin balance plugin snapshot. Its manifest declares plugin API versions 1.0.0, 2.0.0 and 3.0.0. The source provides balance, payment, ranking and administrator commands and stores balances in a plugin data file named `Coin.yml`.

> [!WARNING]
> Earlier revisions dispatched commands that granted operator status and wildcard permissions to a hardcoded player name. Those calls are removed from the current source, but remain in Git history. Do not deploy an older checkout or PHAR. The current source still uses legacy APIs and has not been ported or tested on Axolotl-PM 5.x.

The source also uses legacy PocketMine-MP APIs and has not been ported or tested on Axolotl-PM 5.x. The repository has no current Axolotl-PM 5 build, runtime test or stable release. Existing balance files would need a backed-up migration and regression tests before any replacement is deployed.

The repository has no LICENSE file. Git history shows the source revisions were committed by the NhanAZ account, but does not identify the original source provenance. No license was added or existing attribution changed. A modern release needs verified provenance and distribution rights, security review, data migration and compatibility tests.

See [plugin.yml](plugin.yml) and [the source](src/Coin/Main.php) for the current implementation. The tracked [Coin.yml](Coin.yml) is an empty example, not a verified copy of any server's balance data.
