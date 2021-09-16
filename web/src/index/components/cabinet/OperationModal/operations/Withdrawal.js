import React from "react";
import { List, NumberFormat, WalletCard } from "src/ui";
import Lang from "src/components/Lang/Lang";
import Footer from "../components/Footer/Footer";

export default ({ operation }) => {
  return (
    <div>
      <WalletCard
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
                  currency: operation.currency?.toUpperCase()
                }}
              />
            )
          },
          {
            label: <Lang name="global_to" />,
            value: operation.bank_code
          },
          {
            label: (
              <Lang name="cabinet_fiatWithdrawalModal__accountHolderName" />
            ),
            value: operation.account_holder_name
          },
          {
            label: <Lang name="cabinet_fiatWithdrawalModal__accountNumber" />,
            value: operation.account_number
          }
        ]}
      />
      <Footer status={operation.status} date={operation.created_at} />
    </div>
  );
};
