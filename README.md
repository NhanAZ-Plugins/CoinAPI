# CoinAPI

CoinAPI stores player balances in the plugin data file and provides commands to inspect, transfer, rank and administer coins.

## Compatibility

The plugin declares minimum Axolotl-PM API 5.0.0. Its build workflow runs PHPStan at maximum level and builds against the exact API 5.0.0 and 5.49.1 source revisions. This does not prove compatibility with every release in the API 5.x line. See the [build workflow](https://github.com/NhanAZ-Plugins/CoinAPI/actions/workflows/build.yml) for the tested revisions and run results.

## Commands

| Command | Purpose |
| --- | --- |
| /mycoin | Show your balance |
| /seecoin &lt;player&gt; | Show an online player's balance |
| /paycoin &lt;player&gt; &lt;amount&gt; | Transfer coins to an online player |
| /topcoin [page] | Show the balance leaderboard |
| /setcoin &lt;player&gt; &lt;amount&gt; | Set an online player's balance; operator permission |
| /givecoin &lt;player&gt; &lt;amount&gt; | Add coins to an online player's balance; operator permission |
| /takecoin &lt;player&gt; &lt;amount&gt; | Remove coins from an online player's balance; operator permission |
| /helpcoin | Show command help |

Amounts must be finite and nonnegative; transfers, credits and debits require a value greater than zero. Existing Coin.yml entries remain in the same plugin data path. Invalid legacy values are not counted as a balance or shown on the leaderboard, and unrelated entries are retained when saving.

## Verification and provenance

The workflow checks the ledger behavior, PHPStan at maximum level, PHAR build and inspection at pinned API 5 revisions, then starts the built plugin on a checksum-verified Axolotl-PM 5.49.1 server. The repository has no LICENSE file. Existing source history is retained and no license grant is inferred.
