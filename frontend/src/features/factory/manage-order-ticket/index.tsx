"use client";
import { Tabs } from "antd";
import React from "react";
import ProductionOrder from "./product-order";
import RawMaterialRelease from "./raw-material-release";
import FinishedGoodsReceipt from "./finished-goods-receipt";
import { useTranslations } from "next-intl";

const ManageOrderTicket = () => {
  const t = useTranslations("Factory.manage_order_ticket");

  return (
    <Tabs
      items={[
        {
          label: t("production_order"),
          key: "1",
          children: <ProductionOrder />,
        },
        {
          label: t("raw_material_release"),
          key: "2",
          children: <RawMaterialRelease />,
        },
        {
          label: t("finished_goods_receipt"),
          key: "3",
          children: <FinishedGoodsReceipt />,
        },
      ]}
    />
  );
};

export default ManageOrderTicket;
