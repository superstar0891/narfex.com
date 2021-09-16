import React, { memo, useCallback } from "react";
import { useDispatch } from "react-redux";
import {
  HistoryItem,
  Message,
  NumberFormat,
  WalletAddress
} from "../../../../ui";
import Lang from "../../../../components/Lang/Lang";
import { getCurrencyInfo, openStateModal } from "../../../../actions";
import { getLang, ucfirst } from "src/utils/index";

import { ReactComponent as SwitchIcon } from "src/asset/24px/loop.svg";
import { ReactComponent as SendIcon } from "src/asset/24px/send.svg";
import { ReactComponent as ClockIcon } from "src/asset/24px/clock.svg";
import { ReactComponent as ReceiveIcon } from "src/asset/24px/receive.svg";
import { ReactComponent as AttentionIcon } from "src/asset/24px/attention.svg";
import { ReactComponent as PercentIcon } from "src/asset/24px/percent.svg";
import { ReactComponent as ExchangeIcon } from "src/asset/24px/candles.svg";
import { ReactComponent as UsersIcon } from "src/asset/24px/users.svg";
import { ReactComponent as InvestIcon } from "src/asset/24px/invest.svg";
import { notificationMarkAsRead } from "../../../../actions/cabinet/notifications";

export default memo(({ item, type: balanceType }) => {
  const dispatch = useDispatch();

  const {
    type,
    status,
    amount,
    address,
    unread,
    from,
    to,
    login,
    bank_code: bankCode,
    primary_amount: primaryAmount,
    secondary_amount: secondaryAmount,
    browser_name: browserName,
    browser_version: browserVersion,
    ip_address: ipAddress,
    // is_mobile: isMobile,
    is_mobile_application: isMobileApplication,
    platform_name: platformName
    // platform_version: platformVersion
  } = item;

  const handleClick = useCallback(() => {
    if (item.unread) {
      dispatch(notificationMarkAsRead(item.id));
    }

    openStateModal("operation", {
      operation: {
        ...item,
        balanceType
      }
    });
  }, [balanceType, dispatch, item]);

  const primaryCurrency = getCurrencyInfo(
    item?.primary_currency || item?.currency
  );
  const secondaryCurrency = getCurrencyInfo(item?.secondary_currency);

  switch (type) {
    case "withdrawal":
      return (
        <HistoryItem
          unread={unread}
          onClick={handleClick}
          type={status}
          icon={status === "pending" ? <ClockIcon /> : <SendIcon />}
          label={<Lang name="cabinet_fiatWithdrawalModal_title" />}
          // status={status}
          header={ucfirst(bankCode)}
          headerSecondary={
            <NumberFormat
              color={status === "failed"}
              number={-amount}
              currency={primaryCurrency.abbr}
            />
          }
        />
      );
    case "transaction_send":
      return (
        <HistoryItem
          unread={unread}
          onClick={handleClick}
          type={status}
          icon={status === "pending" ? <ClockIcon /> : <SendIcon />}
          label={<Lang name="cabinet__historyItemTitle_transfer_send" />}
          // status={status}
          header={<WalletAddress address={address} />}
          headerSecondary={
            <NumberFormat number={-amount} currency={primaryCurrency.abbr} />
          }
        />
      );
    case "transfer_send":
      return (
        <HistoryItem
          unread={unread}
          onClick={handleClick}
          icon={<SendIcon />}
          label={<Lang name="cabinet__historyItemTitle_transfer_send" />}
          header={<WalletAddress isUser address={address} />}
          headerSecondary={
            <NumberFormat number={-amount} currency={primaryCurrency.abbr} />
          }
        />
      );
    case "transfer_receive":
      return (
        <HistoryItem
          unread={unread}
          type="success"
          onClick={handleClick}
          icon={<ReceiveIcon />}
          label={<Lang name="cabinet__historyItemTitle_transfer_receive" />}
          header={<WalletAddress isUser address={address} />}
          headerSecondary={
            <NumberFormat
              symbol
              color
              number={amount}
              currency={primaryCurrency.abbr}
            />
          }
        />
      );
    case "refill":
      return (
        <HistoryItem
          unread={unread}
          type={status || "success"}
          onClick={handleClick}
          icon={status === "pending" ? <ClockIcon /> : <ReceiveIcon />}
          label={<Lang name="cabinet__historyItemTitle_refill" />}
          header={ucfirst(bankCode)}
          headerSecondary={
            <NumberFormat
              color
              symbol
              type={status || "success"}
              number={amount}
              currency={primaryCurrency.abbr}
            />
          }
        />
      );
    case "transaction_receive":
      return (
        <HistoryItem
          unread={unread}
          onClick={handleClick}
          type={status}
          icon={status === "pending" ? <ClockIcon /> : <ReceiveIcon />}
          label={<Lang name="cabinet__historyItemTitle_transfer_receive" />}
          header={address}
          headerSecondary={
            <NumberFormat
              symbol
              color
              number={amount}
              currency={primaryCurrency.abbr}
            />
          }
        />
      );
    case "internal_transaction":
      const isReceive = balanceType.includes(to);
      return (
        <HistoryItem
          unread={unread}
          onClick={handleClick}
          type={isReceive ? "success" : undefined}
          icon={
            // TODO
            [from, to].includes("exchange") ? <ExchangeIcon /> : <UsersIcon />
          }
          label={
            <Lang
              name={
                isReceive
                  ? "cabinet__historyItemTitle_internal_transaction_receive"
                  : "cabinet__historyItemTitle_internal_transaction_send"
              }
            />
          }
          header={
            <Lang
              name={
                "cabinet__historyItemType_internal_transaction_" +
                (isReceive ? from : to)
              }
            />
          }
          headerSecondary={
            <NumberFormat
              symbol
              color={isReceive}
              number={isReceive ? amount : -amount}
              currency={primaryCurrency.abbr}
            />
          }
        />
      );
    case "swap":
    case "buy_token":
      return (
        <HistoryItem
          unread={unread}
          onClick={handleClick}
          type="primary"
          icon={<SwitchIcon />}
          label={<Lang name="cabinet__historyItemTitle_swap" />}
          header={
            <NumberFormat
              symbol
              number={-primaryAmount}
              currency={primaryCurrency.abbr}
            />
          }
          headerSecondary={
            <NumberFormat
              symbol
              color
              number={secondaryAmount}
              currency={secondaryCurrency.abbr}
            />
          }
          smallText={
            <Lang
              name="cabinet_historyItem_got"
              params={{ currency: primaryCurrency.name }}
            />
          }
          smallTextSecondary={
            <Lang
              name="cabinet_historyItem_gave"
              params={{ currency: secondaryCurrency.name }}
            />
          }
        />
      );
    case "bank_card_refill_reject":
      return (
        <HistoryItem
          unread={unread}
          onClick={handleClick}
          type="failed"
          icon={<ReceiveIcon />}
          label={
            <Lang name="cabinet__historyItemTitle_bank_card_refill_reject" />
          }
          status="rejected"
          header={
            <NumberFormat
              symbol
              number={primaryAmount}
              currency={primaryCurrency.abbr}
            />
          }
          headerSecondary={
            <NumberFormat
              symbol
              color
              number={amount}
              currency={primaryCurrency.abbr}
            />
          }
        />
      );
    case "saving_accrual":
      return (
        <HistoryItem
          unread={unread}
          onClick={handleClick}
          type={"success"}
          icon={<InvestIcon />}
          label={<Lang name="cabinet__historyItemLabel_saving_accrual" />}
          header={<Lang name="cabinet__historyItemTitle_saving_accrual" />}
          headerSecondary={
            <NumberFormat
              symbol
              color
              number={amount}
              currency={primaryCurrency.abbr}
            />
          }
        />
      );
    case "user_authorize":
      return (
        <HistoryItem
          unread={unread}
          onClick={handleClick}
          type="primary"
          icon={<AttentionIcon />}
          header={<Lang name="cabinet__historyItemTitle_user_authorize" />}
          smallText={
            <Lang
              name="cabinet__historyItemTitle_user_authorize_text"
              params={{
                device: isMobileApplication
                  ? [
                      [
                        getLang("global_applicationFor", true),
                        platformName
                      ].join(", "),
                      getLang("global_ipAddress", true) + ": " + ipAddress
                    ].join(", ")
                  : [
                      [
                        getLang("global_webSite", true),
                        browserName,
                        browserVersion
                      ].join(" "),
                      getLang("global_ipAddress", true) + ": " + ipAddress
                    ].join(", ")
              }}
            />
          }
          headerSecondary={
            <NumberFormat
              symbol
              color
              number={amount}
              currency={primaryCurrency.abbr}
            />
          }
        />
      );
    case "promo_code_reward":
      return (
        <HistoryItem
          unread={unread}
          onClick={handleClick}
          type={"success"}
          icon={<PercentIcon />}
          label={<Lang name="cabinet__historyItemTitle_promo_code_reward" />}
          header={<WalletAddress isUser address={login} />}
          headerSecondary={
            <NumberFormat
              symbol
              color
              number={amount}
              currency={primaryCurrency.abbr}
            />
          }
        />
      );
    default:
      return <Message type="error">Error type "{type}"</Message>;
  }
});
