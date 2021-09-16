import "./Menu.less";
import React from "react";
import { connect } from "react-redux";
import { Switch } from "src/ui";
import { setEditMode, saveMethod } from "src/actions/documentation";
import router from "../../../../router";
import { classNames as cn } from "src/utils";
import * as PAGES from "../../../constants/pages";
import { sortDocSchema } from "../../../containers/documentation/utils";
import { userRole } from "../../../../actions/cabinet/profile";

const DocumentationMenu = props => {
  const handleApiMenuClick = (path, item) => () => {
    if (props.loading) return false;
    if (item.key) {
      router.navigate(PAGES.DOCUMENTATION_API_METHOD, {
        key: item.key
      });
    } else {
      router.navigate(PAGES.DOCUMENTATION_API_LIST, { path: path.join("-") });
    }
  };

  const findMethod = (schema, key, methodName) => {
    if (schema.key) {
      return schema.key === key;
    } else {
      return (
        (Object.keys(schema).length &&
          Object.values(schema)
            .map(item => {
              return findMethod(item, key);
            })
            .filter(i => i)[0]) ||
        false
      );
    }
  };

  const isCurrentPath = (path1, path2) =>
    path2.every((item, i) => item === path1[i]);

  const renderItems = (items, path = []) => {
    return items ? (
      <div className="DocumentationMenu__list">
        {Object.keys(items)
          .sort(sortDocSchema(items))
          .map(item => {
            const currentPath = [...path, item];
            const currentItem = items[item];
            const key = props.route.params.key;
            const open =
              props.route.params.path &&
              isCurrentPath(props.route.params.path.split("-"), currentPath) &&
              currentPath.join("-") !== props.route.params.path;
            const includeMethod = findMethod(currentItem, key) || open;
            const active =
              props.route.params.path === item ||
              (key && currentItem.key === key) ||
              currentPath.join("-") === props.route.params.path;

            return (
              <div
                key={item}
                className={cn("DocumentationMenu__list__item", {
                  active: active || includeMethod
                })}
              >
                <div
                  className={cn("DocumentationMenu__list__item__title", {
                    active: active || (includeMethod && active)
                  })}
                  onClick={handleApiMenuClick(currentPath, currentItem)}
                >
                  {items[item].name === "Default"
                    ? null
                    : items[item].name || item}{" "}
                  {items[item].method}
                  {/*<Label type={items[item].method} />*/}
                </div>
                {items[item] &&
                  items[item].path === undefined &&
                  !active &&
                  includeMethod &&
                  renderItems(items[item], currentPath)}
              </div>
            );
          })}
      </div>
    ) : null;
  };

  return (
    <div className="DocumentationMenu">
      {userRole("translator") && (
        <div className="DocumentationMenu__controls">
          <div className="DocumentationMenu__editMode">
            <span>Edit mode</span>
            <Switch on={props.editMode} onChange={props.setEditMode} />
          </div>
        </div>
      )}
      <div className="DocumentationMenu__list">
        {Object.values(props.staticPages).map(page => (
          <div
            key={page.url}
            className={cn("DocumentationMenu__list__item", {
              active: props.route.params.page === page.url
            })}
          >
            <div
              className={cn("DocumentationMenu__list__item__title")}
              onClick={() => {
                props.loading ||
                  router.navigate(PAGES.DOCUMENTATION_PAGE, { page: page.url });
              }}
            >
              {page.title}
            </div>
          </div>
        ))}
        <div
          className={cn("DocumentationMenu__list__item", {
            active: PAGES.DOCUMENTATION_API === props.route.name
          })}
        >
          <div
            className={cn("DocumentationMenu__list__item__title")}
            onClick={() => {
              router.navigate(PAGES.DOCUMENTATION_API);
            }}
          >
            API
          </div>
          {[
            PAGES.DOCUMENTATION_API_LIST,
            PAGES.DOCUMENTATION_API_METHOD
          ].includes(props.route.name) && renderItems(props.schema)}
        </div>
      </div>
    </div>
  );
};

export default connect(
  state => ({
    saveStatus: state.documentation.loadingStatus.save,
    loading: [
      state.documentation.loadingStatus.page,
      state.documentation.loadingStatus.method
    ].includes("loading"),
    editMode: state.documentation.editMode,
    items: state.documentation.menu,
    schema: state.documentation.schema,
    staticPages: state.documentation.staticPages,
    route: state.router.route
  }),
  {
    setEditMode,
    saveMethod
  }
)(DocumentationMenu);
