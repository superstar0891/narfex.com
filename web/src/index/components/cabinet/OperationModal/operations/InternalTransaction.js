import React from "react";
import { List, WalletCard } from "src/ui";
import Lang from "src/components/Lang/Lang";
import Footer from "../components/Footer/Footer";

export default ({ operation }) => {
  const isReceive = operation.balanceType.includes(operation.to);

  return (
    <div>
      <WalletCard
        title={<Lang name="global_amount" />}
        balance={isReceive ? operation.amount : -operation.amount}
        currency={operation.currency}
        status={isReceive ? "success" : undefined}
        symbol
      />
      <List
        items={[
          {
            label: <Lang name="global_from" />,
            value: isReceive ? (
              <Lang
                name={
                  "cabinet__historyItemType_internal_transaction_" +
                  operation.from
                }
              />
            ) : (
              <Lang
                name="cabinet_operationModal_myWallet"
                params={{
                  currency: operation.currency.toUpperCase()
                }}
              />
            )
          },
          {
            label: <Lang name="global_to" />,
            value: !isReceive ? (
              <Lang
                name={
                  "cabinet__historyItemType_internal_transaction_" +
                  operation.to
                }
              />
            ) : (
              <Lang
                name="cabinet_operationModal_myWallet"
                params={{
                  currency: operation.currency.toUpperCase()
                }}
              />
            )
          }
        ].filter(Boolean)}
      />
      <Footer status={"success"} date={operation.created_at} />
    </div>
  );
};
