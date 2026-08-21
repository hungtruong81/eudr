"use client";
import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import BaseSheet from "@/components/shared/base-sheet";
import {
  IGetMyLandShare,
  myLandShare,
} from "@/features/manage-land/land/actions-share";
import { ICoordinate, IPlot } from "@/features/manage-land/land/types";
import { useQuery } from "@tanstack/react-query";
import { Modal, Row, Col, Space, Table, Typography } from "antd";
import { useMemo, useState } from "react";
import { IConnection } from "../types";

import { GoogleMap, InfoWindow, Polygon } from "@react-google-maps/api";

const { Text } = Typography;

const containerStyle = {
  width: "100%",
  height: "100%",
  minHeight: "400px",
  borderRadius: "8px",
};

const PlotMap = ({ plot }: { plot: IPlot }) => {
  const tConnection = useTranslations("Connection");

  const detailColumns = [
    {
      title: tConnection("land_info"),
      dataIndex: "label",
      key: "label",
      width: "40%",
      render: (text: string) => <Text type="secondary">{text}</Text>,
    },
    {
      title: tConnection("land_value"),
      dataIndex: "value",
      key: "value",
      render: (text: any) => <Text strong>{text ?? "—"}</Text>,
    },
  ];
  const [infoPosition, setInfoPosition] = useState<{
    lat: number;
    lng: number;
  } | null>(null);
  const { coordinates } = plot;

  const center = useMemo(() => {
    if (!coordinates || coordinates.length === 0) {
      return { lat: 10.762622, lng: 106.660172 };
    }
    const latSum = coordinates.reduce((sum, coord) => sum + coord.lat, 0);
    const lngSum = coordinates.reduce((sum, coord) => sum + coord.lng, 0);
    return {
      lat: latSum / coordinates.length,
      lng: lngSum / coordinates.length,
    };
  }, [coordinates]);

  return (
    <GoogleMap
      mapContainerStyle={containerStyle}
      center={center}
      zoom={17}
      options={{
        mapTypeId: "satellite",
        streetViewControl: false,
        mapTypeControl: true,
      }}>
      {coordinates && coordinates.length > 0 && (
        <Polygon
          paths={coordinates}
          options={{
            fillColor: "#52c41a",
            fillOpacity: 0.35,
            strokeColor: "#389e0d",
            strokeOpacity: 1,
            strokeWeight: 2,
          }}
          onClick={(e) => {
            if (e.latLng) {
              setInfoPosition({ lat: e.latLng.lat(), lng: e.latLng.lng() });
            }
          }}
        />
      )}
      {infoPosition && (
        <InfoWindow
          position={infoPosition}
          onCloseClick={() => setInfoPosition(null)}>
          <div style={{ minWidth: 220 }}>
            <Table
              dataSource={[
                {
                  label: tConnection("plot_name"),
                  value: plot.plot_name?.toUpperCase(),
                },
                {
                  label: tConnection("plot_code"),
                  value: plot.plot_code?.toUpperCase(),
                },
                { label: tConnection("area"), value: `${plot.land_area} ha` },
                { label: tConnection("farmer"), value: plot.farmer_name },
                {
                  label: tConnection("crop"),
                  value: plot.crop_type || plot.plant_type,
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
  );
};

interface IMyLandShareModalProps {
  open: boolean;
  onClose: () => void;
  target_user_connect: IConnection | null;
  owner_user_id: number | null;
}

import { useTranslations } from "next-intl";
import AppModal from "@/components/modal";

const MyLandShareModal = ({
  open,
  onClose,
  target_user_connect,
  owner_user_id,
}: IMyLandShareModalProps) => {
  const tCommon = useTranslations("Common");
  const tConnection = useTranslations("Connection");

  const [params, setParams] = useState<Partial<IGetMyLandShare>>({
    owner_id: owner_user_id!,
    status: "all",
    page: 1,
    limit: 10,
  });

  const detailColumns = [
    {
      title: tConnection("land_info"),
      dataIndex: "label",
      key: "label",
      width: "40%",
      render: (text: string) => <Text type="secondary">{text}</Text>,
    },
    {
      title: tConnection("land_value"),
      dataIndex: "value",
      key: "value",
      render: (text: any) => <Text strong>{text ?? "—"}</Text>,
    },
  ];

  const [selectedPlot, setSelectedPlot] = useState<IPlot | null>(null);

  const { data: myLandShares } = useQuery({
    queryKey: ["my-land-share", owner_user_id],
    queryFn: () => myLandShare(params),
    enabled: open,
  });

  const columns: CustomColumnTypeTable<IPlot>[] = [
    {
      title: tConnection("plot_code"),
      dataIndex: "plot_code",
      render: (text) => text?.toUpperCase(),
    },
    { title: tConnection("area"), dataIndex: "land_area" },
    { title: tConnection("crop"), dataIndex: "crop_type" },
    { title: tConnection("year_of_planting"), dataIndex: "year_of_planting" },
    { title: tCommon("address"), dataIndex: "province_name" },
    { title: tCommon("status"), dataIndex: "status" },
  ];

  return (
    <>
      <BaseSheet
        title={tConnection("shared_land_list")}
        open={open}
        onClose={onClose}
        extra={null}>
        <Space orientation="vertical" style={{ width: "100%" }}>
          <p>
            {tConnection("share_info")} {tCommon("phone_number")}:{" "}
            {target_user_connect?.phone} | {tCommon("full_name").toUpperCase()}:{" "}
            {target_user_connect?.full_name} | {tConnection("user_group")}:{" "}
            {target_user_connect?.user_roles
              ?.map((role) => role.description)
              .join(", ")}
          </p>
          <CustomTable
            tableId="my-land-share"
            dataSource={myLandShares?.data.records}
            columns={columns}
            rowKey="plot_id"
            pagination={{
              current: params.page,
              pageSize: params.limit,
              total: myLandShares?.data.total_records,
            }}
            onChange={(pagination) => {
              setParams((prev) => ({
                ...prev,
                page: pagination.current,
                limit: pagination.pageSize,
              }));
            }}
            onRow={(record) => ({
              onClick: () => setSelectedPlot(record),
              style: { cursor: "pointer" },
            })}
          />
        </Space>
      </BaseSheet>

      <AppModal
        title={`${tConnection("land_detail")}: ${selectedPlot?.plot_code?.toUpperCase()}`}
        open={!!selectedPlot}
        onCancel={() => setSelectedPlot(null)}
        footer={null}
        width={1024}
        centered>
        {selectedPlot && (
          <Row gutter={[24, 24]} style={{ marginTop: 16 }}>
            <Col xs={24} md={10} lg={8}>
              <Table
                dataSource={[
                  {
                    label: tConnection("plot_code"),
                    value: selectedPlot.plot_code?.toUpperCase(),
                  },
                  {
                    label: tConnection("plot_name"),
                    value: selectedPlot.plot_name || tConnection("not_updated"),
                  },
                  {
                    label: tConnection("area"),
                    value: `${selectedPlot.land_area} ha`,
                  },
                  {
                    label: tCommon("address"),
                    value:
                      selectedPlot.address ||
                      selectedPlot.plantation_name ||
                      selectedPlot.province_name,
                  },
                  {
                    label: tConnection("crop"),
                    value: selectedPlot.crop_type || selectedPlot.plant_type,
                  },
                  {
                    label: tConnection("year_of_planting"),
                    value: selectedPlot.year_of_planting,
                  },
                  {
                    label: tConnection("coordinates"),
                    value: (
                      <div style={{ maxHeight: "150px", overflowY: "auto" }}>
                        {selectedPlot.coordinates?.length > 0 ? (
                          selectedPlot.coordinates.map((coord, index) => (
                            <div key={index}>
                              <Text
                                type="secondary"
                                style={{ fontSize: "13px" }}>
                                P{index + 1}: {coord.lat.toFixed(5)},{" "}
                                {coord.lng.toFixed(5)}
                              </Text>
                            </div>
                          ))
                        ) : (
                          <Text type="secondary">
                            {tConnection("no_coordinates")}
                          </Text>
                        )}
                      </div>
                    ),
                  },
                ]}
                columns={detailColumns}
                pagination={false}
                size="small"
                bordered
                rowKey="label"
                showHeader={false}
              />
            </Col>

            <Col xs={24} md={14} lg={16}>
              {selectedPlot.coordinates &&
              selectedPlot.coordinates.length > 0 ? (
                <PlotMap plot={selectedPlot} />
              ) : (
                <div
                  style={{
                    height: "400px",
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "center",
                    backgroundColor: "#f5f5f5",
                    borderRadius: "8px",
                    border: "1px dashed #d9d9d9",
                  }}>
                  <Text type="secondary">{tConnection("no_map_data")}</Text>
                </div>
              )}
            </Col>
          </Row>
        )}
      </AppModal>
    </>
  );
};

export default MyLandShareModal;
