import React, { useCallback } from "react";
import { useDispatch, useSelector } from "react-redux";
import HistoryTable from "src/index/components/cabinet/HistoryTable/HistoryTable";
import { partnersHistorySelector, partnersStatusSelector } from "src/selectors";
import Paging from "../../../../../components/cabinet/Paging/Paging";
import { partnersFetchHistoryMore } from "../../../../../../actions/cabinet/partners";

export default () => {
  const dispatch = useDispatch();
  const history = useSelector(partnersHistorySelector);
  const status = useSelector(partnersStatusSelector("historyMore"));

  const handleLoadMore = useCallback(() => {
    dispatch(partnersFetchHistoryMore());
  }, [dispatch]);

  // return <HistoryTable header={true} history={history.items} status={status} />;
  return (
    <Paging
      isCanMore={!!history.next && status !== "loading"}
      onMore={handleLoadMore}
      moreButton={!!history.next}
      isLoading={status === "loading"}
    >
      <HistoryTable type={["partners"]} header={true} history={history.items} />
    </Paging>
  );
};
