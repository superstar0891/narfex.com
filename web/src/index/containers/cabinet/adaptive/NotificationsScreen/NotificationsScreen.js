import "./NotificationsScreen.less";
import React, { useEffect, useRef } from "react";
import { useDispatch } from "react-redux";
import PageContainer from "../../../../components/cabinet/PageContainer/PageContainer";
import HistoryTable from "../../../../components/cabinet/HistoryTable/HistoryTable";
import Paging from "../../../../components/cabinet/Paging/Paging";
import { useSelector } from "react-redux";
import { notificationsSelector } from "../../../../../selectors";
import { loadNotifications } from "../../../../../actions/cabinet/notifications";
import { profileSetHasNotifications } from "../../../../../actions";

export default () => {
  const { history, loading } = useSelector(notificationsSelector);
  const historyLength = useRef(history.items.length);
  const dispatch = useDispatch();

  useEffect(() => {
    dispatch(profileSetHasNotifications(false));
    !historyLength.current && dispatch(loadNotifications());
  }, [historyLength, dispatch]);

  return (
    <PageContainer className="CabinetNotificationScreen">
      <Paging
        isCanMore={!!history.next && !loading}
        onMore={() => {
          dispatch(loadNotifications());
        }}
        moreButton={!!history.next}
        isLoading={loading}
      >
        <HistoryTable
          history={history.items.map(item => ({
            ...item.data,
            created_at: item.created_at,
            type: item.type,
            unread: item.unread
          }))}
          status={!history.items.length && loading && "loading"}
        />
      </Paging>
    </PageContainer>
  );
};
