// styles
// external
import createRouter from "router5";
import browserPlugin from "router5-plugin-browser";
import listenersPlugin from "router5-plugin-listeners";
// internal
import * as pages from "./index/constants/pages";
import * as adminPages from "./admin/constants/pages";

export const routes =
  process.env.DOMAIN === "admin"
    ? [
        {
          name: adminPages.MAIN,
          path: "/admin"
        },
        {
          name: adminPages.PANEL,
          path: `/admin/panel`
        },
        {
          name: adminPages.PANEL_PAGE,
          path: `/admin/panel/:page`
        },
        {
          name: adminPages.NOT_FOUND,
          path: "/admin/not_found"
        }
      ]
    : [
        {
          name: pages.MAIN,
          path: "/"
        },
        {
          name: pages.FNDR,
          path: "/fndr"
        },
        {
          name: pages.BUY_BITCOIN,
          path: "/buy_bitcoin"
        },
        {
          name: pages.MENU,
          path: "/menu"
        },
        {
          name: pages.NOTIFICATIONS,
          path: "/notifications"
        },
        {
          name: pages.ABOUT,
          path: "/about"
        },
        {
          name: pages.SITE_EXCHANGE,
          path: `/${pages.SITE_EXCHANGE}`
        },
        {
          name: pages.WALLET,
          path: "/wallet"
        },
        {
          name: pages.WALLET_SWAP,
          path: "/wallet/swap"
        },
        {
          name: pages.WALLET_CRYPTO,
          path: "/wallet/crypto/:currency"
        },
        {
          name: pages.WALLET_FIAT,
          path: "/wallet/fiat/:currency"
        },
        {
          name: pages.CONTACT,
          path: "/contact"
        },
        // {
        //   name: pages.FAQ,
        //   path: "/faq"
        // },
        {
          name: pages.NOT_FOUND,
          path: "/not_found"
        },
        {
          name: pages.SETTINGS,
          path: "/settings"
        },
        {
          name: pages.PARTNERS,
          path: "/partners"
        },
        {
          name: pages.CHANGE_EMAIL,
          path: "/change_email"
        },
        {
          name: pages.REGISTER,
          path: "/register"
        },
        {
          name: pages.RESET_PASSWORD,
          path: "/reset_password"
        },
        // {
        //   name: pages.EXCHANGE,
        //   path: `/${pages.EXCHANGE}`
        // },
        {
          name: pages.MERCHANT,
          path: "/merchant/:merchant/:status"
        },
        {
          name: pages.FEE,
          path: "/fee"
        },
        {
          name: pages.TOKEN,
          path: "/token"
        },
        {
          name: pages.DOCUMENTATION,
          path: "/documentation"
        },
        {
          name: pages.DOCUMENTATION_PAGE,
          path: "/documentation/page/:page"
        },
        {
          name: pages.DOCUMENTATION_API,
          path: "/documentation/api"
        },
        {
          name: pages.DOCUMENTATION_API_LIST,
          path: "/documentation/api/:path"
        },
        {
          name: pages.DOCUMENTATION_API_METHOD,
          path: "/documentation/api/method/:key"
        }
      ];

const params = {
  // defaultRoute: pages.NOT_FOUND,
  allowNotFound: true,
  defaultParams: {},
  strictQueryParams: true,
  trailingSlash: true,
  useTrailingSlash: false,
  queryParamsMode: "loose"
};

let router = createRouter(routes, params);

router.usePlugin(
  browserPlugin({
    base: "",
    useHash: false,
    hashPrefix: "",
    mergeState: true,
    preserveHash: false
    // forceDeactivate: true,
  })
);

router.usePlugin(listenersPlugin());

router.addListener((state, prevState) => {
  if (state.params.path === "/profile") {
    router.navigate(pages.PARTNERS);
  }
});

export default router;
