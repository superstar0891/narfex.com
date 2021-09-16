import React from "react";
import { List } from "src/ui";
import Lang from "src/components/Lang/Lang";
import Footer from "../components/Footer/Footer";
import { getLang } from "../../../../../utils";

export default ({ operation }) => {
  return (
    <div>
      <List
        items={[
          {
            label: <Lang name="global_device" />,
            value: operation.is_mobile_application
              ? [
                  getLang("global_applicationFor", true),
                  operation.platform_name
                ].join(" ")
              : [operation.browser_name, operation.browser_version].join(" ")
          },
          {
            label: <Lang name="global_ipAddress" />,
            value: operation.ip_address
          }
        ]}
      />
      <Footer date={operation.created_at} />
    </div>
  );
};
