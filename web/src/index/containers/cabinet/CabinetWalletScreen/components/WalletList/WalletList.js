import "./WalletList.less";
import * as PAGES from "src/index/constants/pages";
import React from "react";
import { useRoute } from "react-router5";
import { useSelector } from "react-redux";

import Wallet from "../Wallet/Wallet";
import Lang from "../../../../../../components/Lang/Lang";
import { walletSelector } from "../../../../../../selectors";
import { useAdaptive } from "src/hooks";
import { Separator } from "../../../../../../ui";

import { ReactComponent as WalletIcon } from "src/asset/24px/wallet.svg";

export default ({ currency }) => {
  const adaptive = useAdaptive();
  const { route, router } = useRoute();
  const { wallets, balances } = useSelector(walletSelector);

  return (
    <div className="WalletList">
      {!adaptive && (
        <>
          <Wallet
            active={route.name === PAGES.WALLET}
            onClick={() => router.navigate(PAGES.WALLET)}
            title={<Lang name={"cabinet_header_wallet"} />}
            icon={<WalletIcon />}
          />
          {/*<Wallet*/}
          {/*  active={route.name === PAGES.WALLET_SWAP}*/}
          {/*  onClick={() => router.navigate(PAGES.WALLET_SWAP)}*/}
          {/*  title={<Lang name={"cabinet_fiatMarketExchangeTitle"} />}*/}
          {/*  icon={<LoopIcon />}*/}
          {/*/>*/}
          <Separator />
        </>
      )}
      {wallets.map(wallet => (
        <Wallet
          onClick={() => {
            router.navigate(PAGES.WALLET_CRYPTO, { currency: wallet.currency });
          }}
          key={wallet.id}
          active={wallet.currency === currency}
          amount={wallet.amount}
          currency={wallet.currency}
        />
      ))}
      {/*<Separator />*/}
      {/*{balances.map(balance => (*/}
      {/*  <Wallet*/}
      {/*    onClick={() => {*/}
      {/*      router.navigate(PAGES.WALLET_FIAT, { currency: balance.currency });*/}
      {/*    }}*/}
      {/*    key={balance.id}*/}
      {/*    active={balance.currency === currency}*/}
      {/*    amount={balance.amount}*/}
      {/*    currency={balance.currency}*/}
      {/*  />*/}
      {/*))}*/}
    </div>
  );
};
