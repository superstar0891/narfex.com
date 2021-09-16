import React from "react";
import { List, NumberFormat, WalletCard } from "src/ui";
import Lang from "src/components/Lang/Lang";
import Footer from "../components/Footer/Footer";

export default ({ operation }) => {
  return (
    <div>
      <WalletCard
        collor={false}
        title={<Lang name="global_amount" />}
        balance={-operation.amount}
        currency={operation.currency}
        status={operation.status}
      />
      <List
        items={[
          {
            label: <Lang name="global_fee" />,
            value: (
              <NumberFormat
                number={operation.fee}
                currency={operation.currency}
              />
            )
          },
          {
            label: <Lang name="global_from" />,
            value: (
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
            value: operation.address
          },
          operation.txid && {
            label: <Lang name="global_txid" />,
            value: operation.txid
          },
          {
            label: <Lang name="global_confirmations" />,
            value: [
              operation.confirmations,
              operation.required_confirmations
            ].join("/")
          }
        ].filter(Boolean)}
      />
      <Footer status={operation.status} date={operation.created_at} />
    </div>
  );
};
