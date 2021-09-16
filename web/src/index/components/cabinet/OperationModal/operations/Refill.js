import React from "react";
import { List, NumberFormat, WalletCard } from "src/ui";
import Lang from "src/components/Lang/Lang";
import Footer from "../components/Footer/Footer";

export default ({ operation }) => {
  return (
    <div>
      <WalletCard
        title={<Lang name="global_amount" />}
        balance={operation.amount}
        symbol
        currency={operation.currency}
        status={operation.status || "confirmed"}
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
            value: operation.bank_code
          },
          {
            label: <Lang name="global_to" />,
            value: (
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
      <Footer
        status={operation.status || "confirmed"}
        date={operation.created_at}
      />
    </div>
  );
};
