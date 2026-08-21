"use client";

import React, { useCallback, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Table, Tag, Typography, Space, Tabs, Spin, Empty } from "antd";
import { getExternalProductLotById } from "../actions";
import BaseSheet from "@/components/shared/base-sheet";
import { formatDateDDMMYYYY } from "@/lib/utils";
import { getStatusColor, getStatusLabel } from "./external-product-lot-card";
import { GoogleMap, Polygon, InfoWindow } from "@react-google-maps/api";
import { useTranslations } from "next-intl";

const { Text } = Typography;

interface Props {
  open: boolean;
  onClose: () => void;
  productLotId: string | null;
}

const MAP_CONTAINER_STYLE = {
  width: "100%",
  height: "400px",
  borderRadius: "8px",
  border: "1px solid #f0f0f0",
};

const ExternalProductLotDetail = ({ open, onClose, productLotId }: Props) => {
  const t = useTranslations("Sales.external_product_lot");
  const tCommon = useTranslations("Common");
  const tStatus = useTranslations("Status");
  const tAccount = useTranslations("Account");

  const [selectedLand, setSelectedLand] = useState<any>(null);
  const [selectedPosition, setSelectedPosition] =
    useState<google.maps.LatLngLiteral | null>(null);
  const [map, setMap] = useState<google.maps.Map | null>(null);

  const { data: response, isLoading } = useQuery({
    queryKey: ["external-product-lot-detail", productLotId],
    queryFn: () => getExternalProductLotById(productLotId!),
    enabled: !!productLotId && open,
  });

  const record = response?.data;

  const onLoadMap = useCallback(
    (mapInstance: google.maps.Map) => {
      setMap(mapInstance);
      if (record?.lands && record.lands.length > 0) {
        const bounds = new window.google.maps.LatLngBounds();
        let hasCoords = false;
        record.lands.forEach((land: any) => {
          if (land.coordinates && land.coordinates.length > 0) {
            hasCoords = true;
            land.coordinates.forEach((coord: any) => {
              bounds.extend(
                new window.google.maps.LatLng(coord.lat, coord.lng),
              );
            });
          }
        });
        if (hasCoords) {
          mapInstance.fitBounds(bounds);
        }
      }
    },
    [record?.lands],
  );

  const landColumns = [
    {
      title: t("plot_name"),
      dataIndex: "plot_name",
      key: "plot_name",
      render: (text: string) => <Text strong>{text || "—"}</Text>,
    },
    {
      title: t("area_ha"),
      dataIndex: "land_area",
      key: "land_area",
      render: (val: number) => (val ? `${val.toLocaleString()} ha` : "—"),
    },
    {
      title: t("harvest_weight"),
      dataIndex: "harvest_weight",
      key: "harvest_weight",
      render: (val: number) =>
        val ? `${Number(val).toLocaleString()} kg` : "—",
    },
    {
      title: tCommon("address"),
      dataIndex: "address",
      key: "address",
    },
    {
      title: tCommon("notes"),
      dataIndex: "notes",
      key: "notes",
      render: (text: string) => text || "—",
    },
  ];

  const detailColumns = [
    {
      title: tCommon("info"),
      dataIndex: "label",
      key: "label",
      width: "30%",
      render: (text: string) => <Text type="secondary">{text}</Text>,
    },
    {
      title: tCommon("detail"),
      dataIndex: "value",
      key: "value",
      render: (text: any) => <Text strong>{text ?? "—"}</Text>,
    },
  ];

  const tabItems = [
    {
      key: "general",
      label: tCommon("overview"),
      children: (
        <Space orientation="vertical" size="large" style={{ width: "100%" }}>
          <div>
            <Typography.Title level={5}>{t("supplier_info")}</Typography.Title>
            <Table
              dataSource={[
                {
                  label: tAccount("company"),
                  value: record?.supplier_company_name,
                },
                {
                  label: tCommon("factory"),
                  value: record?.supplier_factory_name,
                },
                {
                  label: tCommon("phone_number"),
                  value: record?.supplier_phone,
                },
                { label: tCommon("address"), value: record?.supplier_address },
              ]}
              columns={detailColumns}
              pagination={false}
              size="middle"
              bordered
              rowKey="label"
            />
          </div>

          <div>
            <Typography.Title level={5}>{t("lot_info")}</Typography.Title>
            <Table
              dataSource={[
                {
                  label: t("system_lot_code"),
                  value: record?.product_lot_code?.toUpperCase(),
                },
                {
                  label: t("original_lot_code"),
                  value: record?.original_product_lot_code?.toUpperCase(),
                },
                {
                  label: tCommon("status"),
                  value: (
                    <Tag color={getStatusColor(record?.status)}>
                      {getStatusLabel(record?.status, tStatus)}
                    </Tag>
                  ),
                },
                { label: tCommon("grade"), value: record?.grade },
                {
                  label: t("produced_from"),
                  value: formatDateDDMMYYYY(record?.production_date_from),
                },
                {
                  label: t("produced_to"),
                  value: formatDateDDMMYYYY(record?.production_date_to),
                },
                {
                  label: t("purchase_date"),
                  value: formatDateDDMMYYYY(record?.purchase_date),
                },
                {
                  label: t("purchase_amount"),
                  value: `${Number(record?.purchase_amount).toLocaleString()} VNĐ`,
                },
                { label: t("total_blocks"), value: record?.total_blocks },
                {
                  label: tCommon("total_weight"),
                  value: `${Number(record?.total_weight).toLocaleString()} kg`,
                },
                { label: tCommon("notes"), value: record?.notes },
              ]}
              columns={detailColumns}
              pagination={false}
              size="middle"
              bordered
              rowKey="label"
            />
          </div>
        </Space>
      ),
    },
    {
      key: "lands",
      label: t("plots"),
      children: (
        <Space direction="vertical" size="large" style={{ width: "100%" }}>
          <div style={{ position: "relative" }}>
            <GoogleMap
              mapContainerStyle={MAP_CONTAINER_STYLE}
              center={{ lat: 10, lng: 106 }} // Fallback center
              zoom={10}
              onLoad={onLoadMap}
              options={{ mapTypeId: "satellite" }}>
              {record?.lands?.map((land: any, index: number) => {
                if (!land.coordinates || land.coordinates.length < 3)
                  return null;
                return (
                  <Polygon
                    key={index}
                    paths={land.coordinates}
                    options={{
                      fillColor: "#1890ff",
                      fillOpacity: 0.3,
                      strokeColor: "#1890ff",
                      strokeOpacity: 0.8,
                      strokeWeight: 2,
                    }}
                    onClick={(e) => {
                      if (e.latLng) {
                        setSelectedLand(land);
                        setSelectedPosition({
                          lat: e.latLng.lat(),
                          lng: e.latLng.lng(),
                        });
                      }
                    }}
                  />
                );
              })}

              {selectedLand && selectedPosition && (
                <InfoWindow
                  position={selectedPosition}
                  onCloseClick={() => {
                    setSelectedLand(null);
                    setSelectedPosition(null);
                  }}>
                  <div style={{ padding: "4px" }}>
                    <Typography.Title
                      level={5}
                      style={{ margin: 0, marginBottom: 8 }}>
                      {selectedLand.plot_name || t("unnamed_plot")}
                    </Typography.Title>
                    <Table
                      dataSource={[
                        {
                          label: t("area"),
                          value: `${selectedLand.land_area} ha`,
                        },
                        {
                          label: t("weight"),
                          value: `${Number(selectedLand.harvest_weight).toLocaleString()} kg`,
                        },
                      ]}
                      columns={detailColumns}
                      pagination={false}
                      size="small"
                      bordered
                      rowKey="label"
                      showHeader={false}
                    />
                  </div>
                </InfoWindow>
              )}
            </GoogleMap>
          </div>

          <Table
            dataSource={record?.lands}
            columns={landColumns}
            pagination={false}
            rowKey={(record, index) => index?.toString() || ""}
            size="middle"
            bordered
          />
        </Space>
      ),
    },
    {
      key: "transport",
      label: t("transport"),
      children: (
        <Table
          dataSource={[
            {
              label: tCommon("vehicle"),
              value: record?.transport?.vehicle_license_plate,
            },
            { label: t("driver"), value: record?.transport?.driver_name },
            {
              label: t("driver_phone"),
              value: record?.transport?.driver_phone,
            },
            {
              label: t("transport_date"),
              value: record?.transport?.transport_date,
            },
            { label: t("pickup_time"), value: record?.transport?.pickup_time },
            {
              label: t("delivery_time"),
              value: record?.transport?.delivery_time,
            },
            {
              label: t("pickup_location"),
              value: record?.transport?.pickup_location,
            },
            {
              label: t("delivery_location"),
              value: record?.transport?.delivery_location,
            },
            { label: t("transport_notes"), value: record?.transport?.notes },
          ]}
          columns={detailColumns}
          pagination={false}
          size="middle"
          bordered
          rowKey="label"
        />
      ),
    },
  ];

  return (
    <BaseSheet
      open={open}
      onClose={onClose}
      title={t("detail_title")}
      width={1000}>
      <Spin spinning={isLoading}>
        {record ? (
          <Tabs defaultActiveKey="general" items={tabItems} />
        ) : (
          !isLoading && <Empty description={tCommon("not_found")} />
        )}
      </Spin>
    </BaseSheet>
  );
};

export default ExternalProductLotDetail;
