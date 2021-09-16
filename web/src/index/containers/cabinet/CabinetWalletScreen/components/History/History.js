import React from "react";
import { useSelector } from "react-redux";
import HistoryTable from "src/index/components/cabinet/HistoryTable/HistoryTable";
import {
  walletHistorySelector,
  walletStatusHistorySelector
} from "src/selectors";

export default () => {
  const history = useSelector(walletHistorySelector);
  const status = useSelector(walletStatusHistorySelector);
  return (
    <HistoryTable
      type={["wallet", "fiat"]}
      header={true}
      history={history.items}
      status={status}
    />
  );
};
