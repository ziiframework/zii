<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\web;

use yii\web\UploadedFile;
use yiiunit\framework\web\stubs\ModelStub;
use yiiunit\framework\web\stubs\VendorImage;
use yiiunit\TestCase;

/**
 * @group web
 *
 * @internal
 * @coversNothing
 */
final class UploadedFileTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockApplication();
        $this->generateFakeFiles();
    }

    // Tests :

    public function testGetInstance(): void
    {
        $productImage = UploadedFile::getInstance(new ModelStub(), 'prod_image');
        $vendorImage = VendorImage::getInstance(new ModelStub(), 'vendor_image');

        $this->assertInstanceOf(UploadedFile::className(), $productImage);
        $this->assertInstanceOf(VendorImage::className(), $vendorImage);
    }

    public function testGetInstances(): void
    {
        $productImages = UploadedFile::getInstances(new ModelStub(), 'prod_images');
        $vendorImages = VendorImage::getInstances(new ModelStub(), 'vendor_images');

        foreach ($productImages as $productImage) {
            $this->assertInstanceOf(UploadedFile::className(), $productImage);
        }

        foreach ($vendorImages as $vendorImage) {
            $this->assertInstanceOf(VendorImage::className(), $vendorImage);
        }
    }

    public function testSaveAs(): void
    {
        $tmpImage = UploadedFile::getInstance(new ModelStub(), 'temp_image');
        $targetFile = '@runtime/test_saved_uploaded_file_' . time();

        $this->assertTrue($tmpImage->saveAs($targetFile, $deleteTempFile = false));
        $this->markTestIncomplete("`{$deleteTempFile}` flag simply uses php's move_uploaded_file() method, so this not work in test");

        @unlink($targetFile);
    }

    public function testSaveFileFromMultipartFormDataParser(): void
    {
        $_FILES = [];
        UploadedFile::reset();
        $model = new ModelStub();
        $targetFile = '@runtime/test_saved_uploaded_file_' . time();

        (new MultipartFormDataParserTest())->testParse();
        $_FILES['ModelStub'] = $_FILES['Item']; // $_FILES[Item] here from testParse() above
        $tmpFile = UploadedFile::getInstance($model, 'file');

        $this->assertSame($tmpFile->saveAs($targetFile, $deleteTempFile = false), true);
        $this->assertSame($tmpFile->saveAs($targetFile), true);

        $this->assertSame($tmpFile->saveAs($targetFile), false); // has deleted before

        @unlink($targetFile);
    }

    private function generateFakeFileData()
    {
        return [
            'name' => md5(random_int(0, PHP_INT_MAX)),
            'tmp_name' => md5(random_int(0, PHP_INT_MAX)),
            'type' => 'image/jpeg',
            'size' => random_int(1000, 10000),
            'error' => '0',
        ];
    }

    private function generateTempFileData()
    {
        return [
            'name' => md5(random_int(0, PHP_INT_MAX)),
            'tmp_name' => tempnam(sys_get_temp_dir(), ''),
            'type' => 'image/jpeg',
            'size' => random_int(1000, 10000),
            'error' => '0',
        ];
    }

    private function generateFakeFiles(): void
    {
        $_FILES['ModelStub[prod_image]'] = $this->generateFakeFileData();
        $_FILES['ModelStub[prod_images][]'] = $this->generateFakeFileData();
        $_FILES['ModelStub[prod_images][]'] = $this->generateFakeFileData();
        $_FILES['ModelStub[prod_images][]'] = $this->generateFakeFileData();

        $_FILES['ModelStub[vendor_image]'] = $this->generateFakeFileData();
        $_FILES['ModelStub[vendor_images][]'] = $this->generateFakeFileData();
        $_FILES['ModelStub[vendor_images][]'] = $this->generateFakeFileData();
        $_FILES['ModelStub[vendor_images][]'] = $this->generateFakeFileData();

        $_FILES['ModelStub[temp_image]'] = $this->generateTempFileData();
    }
}
