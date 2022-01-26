<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
    <manifest identifier="cctd0001"
              xmlns="http://www.imsglobal.org/xsd/imsccv1p1/imscp_v1p1"
              xmlns:lom="http://ltsc.ieee.org/xsd/imsccv1p1/LOM/resource"
              xmlns:lomimscc="http://ltsc.ieee.org/xsd/imsccv1p1/LOM/manifest"
              xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
              xsi:schemaLocation="http://www.imsglobal.org/xsd/imsccv1p1/imscp_v1p1 https://www.imsglobal.org/profile/cc/ccv1p1/ccv1p1_imscp_v1p2_v1p0.xsd
                  http://ltsc.ieee.org/xsd/imsccv1p1/LOM/resource https://www.imsglobal.org/profile/cc/ccv1p1/LOM/ccv1p1_lomresource_v1p0.xsd
                  http://ltsc.ieee.org/xsd/imsccv1p1/LOM/manifest https://www.imsglobal.org/profile/cc/ccv1p1/LOM/ccv1p1_lommanifest_v1p0.xsd">
    <metadata>
        <schema>IMS Common Cartridge</schema>
        <schemaversion>1.1.0</schemaversion>
        <lomimscc:lom>
            <lomimscc:general>
                <lomimscc:title>
                    <lomimscc:string language="{{ $lang }}">{{ $course_name }}</lomimscc:string>
                </lomimscc:title>
                <lomimscc:description>
                    <lomimscc:string language="{{ $lang }}">{{ $course_description }}</lomimscc:string>
                </lomimscc:description>
            </lomimscc:general>
        </lomimscc:lom>
    </metadata>
    <organizations>
        <organization identifier="O_1" structure="rooted-hierarchy">
            <item identifier="I_1">
                {!! $organization_items !!}
            </item>
        </organization>
    </organizations>
    <resources>
        {!! $resources !!}
    </resources>
</manifest>