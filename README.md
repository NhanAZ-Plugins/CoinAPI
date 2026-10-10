# CoinAPI

CoinAPI is a legacy PocketMine-MP coin balance plugin snapshot. Its manifest declares plugin API versions 1.0.0, 2.0.0 and 3.0.0. The source provides balance, payment, ranking and administrator commands and stores balances in a plugin data file named `Coin.yml`.

> [!WARNING]
> On startup, the historical source dispatches commands that grant operator status and wildcard permissions to a hardcoded player name. Do not deploy this snapshot on a server. Review and remove that behavior as part of any authorized security repair before considering a port.

The source also uses legacy PocketMine-MP APIs and has not been ported or tested on Axolotl-PM 5.x. The repository has no current Axolotl-PM 5 build, runtime test or stable release. Existing balance files would need a backed-up migration and regression tests before any replacement is deployed.

The repository has no LICENSE file or documented grant to redistribute a modified build. No source, manifest, permission logic or historical messages were changed for this documentation update. A modern release needs verified rights, security review, data migration and compatibility tests.

See [plugin.yml](plugin.yml) and [the historical source](src/Coin/Main.php) for the exact implementation. The tracked [Coin.yml](Coin.yml) is an empty example, not a verified copy of any server's balance data.
