import React from "react";
import { List, WalletAddress, WalletCard } from "src/ui";
import Lang from "src/components/Lang/Lang";
import Footer from "../components/Footer/Footer";

export default ({ operation }) => {
  return (
    <div>
      <WalletCard
        symbol
        title={<Lang name="cabinet_operationModal_receive" />}
        balance={operation.amount}
        status="success"
        currency={operation.currency}
      />
      <List
        items={[
          {
            label: <Lang name="global_from" />,
            value: <WalletAddress isUser address={operation.login} />
          },
          {
            label: <Lang name="global_to" />,
            value: <Lang name="cabinet__historyItemFrom_promo_code_reward" />
          }
        ]}
      />
      <Footer date={operation.created_at} />
    </div>
  );
};
