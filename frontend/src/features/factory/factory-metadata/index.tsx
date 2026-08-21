"use client";
import { Tabs } from "antd";
import Factory from "./factory/components/factory";
import RawMaterialTank from "./raw-material-tank/components/raw-material-tank";
import ProductTank from "./product-tank/components/product-tank";
import ProductType from "./product-type/components/product-type";
import { Cutting } from "./cutting/components/cutting";
import { GongCart } from "./gong-cart/components/gong-cart";
import { ProductChannel } from "./product-channel/components/product-channel";
import { ProductOven } from "./product-oven/components/product-oven";
import { ProductRoller } from "./product-roller/components/product-roller";
import { useTranslations } from "next-intl";

const FactoryPage = () => {
  const t = useTranslations("Factory.metadata");

  return (
    <Tabs
      items={[
        {
          label: t("tab_factory"),
          key: "1",
          children: <Factory />,
        },
        {
          label: t("tab_raw_material_tank"),
          key: "2",
          children: <RawMaterialTank />,
        },
        {
          label: t("tab_product_tank"),
          key: "3",
          children: <ProductTank />,
        },
        {
          label: t("tab_product_type"),
          key: "4",
          children: <ProductType />,
        },
        {
          label: t("tab_cutting"),
          key: "5",
          children: <Cutting />,
        },
        {
          label: t("tab_gong_cart"),
          key: "6",
          children: <GongCart />,
        },
        {
          label: t("tab_product_channel"),
          key: "7",
          children: <ProductChannel />,
        },
        {
          label: t("tab_product_oven"),
          key: "8",
          children: <ProductOven />,
        },
        {
          label: t("tab_product_roller"),
          key: "9",
          children: <ProductRoller />,
        },
      ]}
    />
  );
};

export default FactoryPage;

