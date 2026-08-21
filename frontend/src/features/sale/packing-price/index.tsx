"use client";

import React from "react";
import { Tabs } from "antd";
import { useTranslations } from "next-intl";
import PalletManager from "./pallet/components";
import PriceManager from "./price/components";
import { usePermissions } from "@/contexts/permission-context";

const PackingPricePage = () => {
  const t = useTranslations("Pallet");
  const { trader } = usePermissions();

  const canViewPallet =
    trader.pallet.view.self ||
    trader.pallet.view.own ||
    trader.pallet.view.all ||
    trader.pallet.full;

  const canViewPrice =
    trader.price.view.self ||
    trader.price.view.own ||
    trader.price.view.all ||
    trader.price.full;

  const items = [];

  if (canViewPallet) {
    items.push({
      key: "pallet",
      label: t("tab_title"),
      children: <PalletManager />,
    });
  }

  if (canViewPrice) {
    items.push({
      key: "price",
      label: t("price_tab_title"),
      children: <PriceManager />,
    });
  }

  if (items.length === 0) {
    return (
      <div className="flex items-center justify-center h-full p-8 text-gray-500">
        {t("no_permission") || "You do not have permission to view this page"}
      </div>
    );
  }

  const defaultKey = canViewPallet ? "pallet" : "price";

  return (
    <Tabs
      defaultActiveKey={defaultKey}
      items={items}
      style={{ minHeight: "100%" }}
    />
  );
};

export default PackingPricePage;
