import React from "react";
import { List, NumberFormat, WalletCard } from "src/ui";
import Lang from "src/components/Lang/Lang";
import Footer from "../components/Footer/Footer";

export default ({ operation }) => {
  return (
    <div>
      <WalletCard
        symbol
        title={<Lang name="cabinet_operationModal_receive" />}
        balance={operation.secondary_amount}
        status={operation.status}
        currency={operation.secondary_currency}
      />
      <List
        items={[
          {
            label: <Lang name="cabinet_operationModal_given" />,
            value: (
              <NumberFormat
                number={operation.primary_amount}
                currency={operation.primary_currency}
              />
            )
          }
        ]}
      />
      <Footer status={operation.status} date={operation.created_at} />
    </div>
  );
};
