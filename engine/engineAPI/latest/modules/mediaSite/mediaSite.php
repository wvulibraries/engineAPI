<?php
/**
 * EngineAPI mediaSite module
 * EDAS client object example for PHP
 * These proxy classes were auto generated based on the EDAS WSDL definition.
 *
 * @package EngineAPI\modules\mediaSite
 */

/**
 * @package EngineAPI\modules\mediaSite
 */
class loginRequest {

	/**
	 * @var string
	 */
	public $Username;

	/**
	 * @var string
	 */
	public $Password;

	/**
	 * @var string
	 */
	public $ImpersonationUsername;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class LoginResult {

	/**
	 * @var string
	 */
	public $UserTicket;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class getVersionRequest {

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class GetVersionResult {

	/**
	 * @var string
	 */
	public $Version;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class testRequest {

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class TestResult {

	/**
	 * @var boolean
	 */
	public $Value;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class RequestMessage {

	/**
	 * @var string
	 */
	public $UserTicket;

	/**
	 * @var string
	 */
	public $ImpersonationUsername;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class queryPresentationDetailsRequest extends RequestMessage {

	/**
	 * @var (object)ArrayOfString
	 */
	public $PresentationIdList;

	/**
	 * @var (object)QueryPresentationDetailsFilter
	 */
	public $Filter;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ArrayOfString {

	/**
	 * @var array[0, unbounded] of string
	 */
	public $string;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class QueryPresentationDetailsFilter {

	/**
	 * @var array[1, 1] of ResourcePermissionMask
	 */
	public $PermissionMask;

	/**
	 * @var boolean
	 */
	public $IncludeMediaEncodeProfile;

	/**
	 * @var boolean
	 */
	public $IncludePresenterList;

	/**
	 * @var boolean
	 */
	public $IncludeSuportingLinks;

	/**
	 * @var boolean
	 */
	public $IncludeAttachments;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class QueryPresentationDetailsResult {

	/**
	 * @var (object)ArrayOfPresentationDetails
	 */
	public $Details;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ArrayOfPresentationDetails {

	/**
	 * @var array[0, unbounded] of (object)PresentationDetails
	 */
	public $PresentationDetails;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class PresentationDetails {

	/**
	 * @var string
	 */
	public $Id;

	/**
	 * @var string
	 */
	public $Name;

	/**
	 * @var string
	 */
	public $PresentationRootId;

	/**
	 * @var string
	 */
	public $Description;

	/**
	 * @var dateTime
	 */
	public $AirDateTime;

	/**
	 * @var dateTime
	 */
	public $AirDateTimeUtc;

	/**
	 * @var int
	 */
	public $Duration;

	/**
	 * @var string
	 */
	public $PlayerUrl;

	/**
	 * @var string
	 *     NOTE: Status should follow the following restrictions
	 *     You can have one of the following value
	 *     Scheduled
	 *     OpenForRecord
	 *     Recording
	 *     Recorded
	 *     Uploaded
	 */
	public $Status;

	/**
	 * @var boolean
	 */
	public $IsLive;

	/**
	 * @var boolean
	 */
	public $IsOnDemand;

	/**
	 * @var (object)ArrayOfString
	 */
	public $PresenterIdList;

	/**
	 * @var (object)ArrayOfPresenterContext
	 */
	public $Presenters;

	/**
	 * @var string
	 */
	public $MediaEncodeProfileId;

	/**
	 * @var (object)MediaEncodeProfileDetails
	 */
	public $MediaEncodeProfile;

	/**
	 * @var string
	 */
	public $PlayerId;

	/**
	 * @var string
	 */
	public $FileServerUrl;

	/**
	 * @var string
	 */
	public $VideoUrl;

	/**
	 * @var string
	 */
	public $OnDemandFileName;

	/**
	 * @var string
	 */
	public $TimeZoneId;

	/**
	 * @var string
	 */
	public $TimeZoneAbbreviation;

	/**
	 * @var (object)ArrayOfSupportingLink
	 */
	public $SupportingLinks;

	/**
	 * @var int
	 */
	public $SlideCount;

	/**
	 * @var int
	 */
	public $ChapterPointCount;

	/**
	 * @var string
	 */
	public $FolderId;

	/**
	 * @var string
	 */
	public $FolderPath;

	/**
	 * @var string
	 */
	public $Owner;

	/**
	 * @var (object)ArrayOfPresentationAttachment
	 */
	public $AttachmentList;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ArrayOfPresenterContext {

	/**
	 * @var array[0, unbounded] of (object)PresenterContext
	 */
	public $PresenterContext;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class MediasiteContext {

	/**
	 * @var string
	 */
	public $Id;

	/**
	 * @var string
	 */
	public $Name;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class PresenterContext extends MediasiteContext {

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class MediaEncodeProfileDetails {

	/**
	 * @var string
	 */
	public $Id;

	/**
	 * @var string
	 */
	public $Name;

	/**
	 * @var string
	 */
	public $Description;

	/**
	 * @var (object)ArrayOfVideoEncodingDetails
	 */
	public $VideoEncodings;

	/**
	 * @var (object)ArrayOfAudioEncodingDetails
	 */
	public $AudioEncodings;

	/**
	 * @var (object)SlideCaptureDetails
	 */
	public $SlideCaptureSettings;

	/**
	 * @var string
	 */
	public $Owner;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ArrayOfVideoEncodingDetails {

	/**
	 * @var array[0, unbounded] of (object)VideoEncodingDetails
	 */
	public $VideoEncodingDetails;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class VideoEncodingDetails {

	/**
	 * @var string
	 */
	public $Name;

	/**
	 * @var string
	 */
	public $Description;

	/**
	 * @var string
	 */
	public $MediaSubType;

	/**
	 * @var int
	 */
	public $Height;

	/**
	 * @var int
	 */
	public $Width;

	/**
	 * @var int
	 */
	public $EffectiveBitRate;

	/**
	 * @var int
	 */
	public $FrameRate;

	/**
	 * @var int
	 */
	public $BufferSize;

	/**
	 * @var int
	 */
	public $ImageQuality;

	/**
	 * @var int
	 */
	public $KeyFrame;

	/**
	 * @var (object)AudioEncodingDetails
	 */
	public $AudioEncoding;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class AudioEncodingDetails {

	/**
	 * @var string
	 */
	public $Name;

	/**
	 * @var string
	 */
	public $Description;

	/**
	 * @var string
	 */
	public $MediaSubType;

	/**
	 * @var int
	 */
	public $BitRate;

	/**
	 * @var int
	 */
	public $SampleRate;

	/**
	 * @var int
	 */
	public $Channels;

	/**
	 * @var int
	 */
	public $EncodingBits;

	/**
	 * @var string
	 *     NOTE: EncodingMode should follow the following restrictions
	 *     You can have one of the following value
	 *     None
	 *     ConstantBitRate
	 *     VariableBitRate
	 */
	public $EncodingMode;

	/**
	 * @var string
	 *     NOTE: EncodingType should follow the following restrictions
	 *     You can have one of the following value
	 *     None
	 *     Audio
	 *     AudioVideo
	 */
	public $EncodingType;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ArrayOfAudioEncodingDetails {

	/**
	 * @var array[0, unbounded] of (object)AudioEncodingDetails
	 */
	public $AudioEncodingDetails;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class SlideCaptureDetails {

	/**
	 * @var string
	 */
	public $Description;

	/**
	 * @var int
	 */
	public $ChangeSensitivity;

	/**
	 * @var int
	 */
	public $MaxScanRate;

	/**
	 * @var int
	 */
	public $Quality;

	/**
	 * @var string
	 *     NOTE: Options should follow the following restrictions
	 *     You can have one of the following value
	 *     None
	 *     IsCaptureInWideScreen
	 *     IsDefault
	 */
	public $Options;

	/**
	 * @var (object)StabilizationDetails
	 */
	public $Stabilization;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class StabilizationDetails {

	/**
	 * @var string
	 */
	public $Description;

	/**
	 * @var int
	 */
	public $Interval;

	/**
	 * @var int
	 */
	public $BypassInterval;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ArrayOfSupportingLink {

	/**
	 * @var array[0, unbounded] of (object)SupportingLink
	 */
	public $SupportingLink;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class SupportingLink {

	/**
	 * @var string
	 */
	public $Description;

	/**
	 * @var string
	 */
	public $Url;

	/**
	 * @var int
	 */
	public $OrderNumber;

	/**
	 * @var string
	 */
	public $PresentationId;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ArrayOfPresentationAttachment {

	/**
	 * @var array[0, unbounded] of (object)PresentationAttachment
	 */
	public $PresentationAttachment;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class PresentationAttachment {

	/**
	 * @var int
	 */
	public $Id;

	/**
	 * @var string
	 */
	public $Name;

	/**
	 * @var string
	 *     NOTE: Type should follow the following restrictions
	 *     You can have one of the following value
	 *     Unknown
	 *     Captioning
	 *     Thumbnail
	 */
	public $Type;

	/**
	 * @var string
	 */
	public $PresentationId;

	/**
	 * @var int
	 */
	public $ServerId;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class queryTimeZonesRequest extends RequestMessage {

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class QueryTimeZonesResult {

	/**
	 * @var (object)ArrayOfTimeZoneContext
	 */
	public $TimeZones;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ArrayOfTimeZoneContext {

	/**
	 * @var array[0, unbounded] of (object)TimeZoneContext
	 */
	public $TimeZoneContext;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class TimeZoneContext extends MediasiteContext {

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class queryMediaEncodeProfilesRequest extends RequestMessage {

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class QueryMediaEncodeProfilesResult {

	/**
	 * @var (object)ArrayOfMediaEncodeProfileContext
	 */
	public $MediaEncodeProfiles;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ArrayOfMediaEncodeProfileContext {

	/**
	 * @var array[0, unbounded] of (object)MediaEncodeProfileContext
	 */
	public $MediaEncodeProfileContext;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class MediaEncodeProfileContext extends MediasiteContext {

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class queryPresentersRequest extends RequestMessage {

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class QueryPresentersResult {

	/**
	 * @var (object)ArrayOfPresenterContext
	 */
	public $Presenters;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class queryServerGroupsRequest extends RequestMessage {

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class QueryServerGroupsResult {

	/**
	 * @var (object)ArrayOfServerGroupContext
	 */
	public $ServerGroups;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ArrayOfServerGroupContext {

	/**
	 * @var array[0, unbounded] of (object)ServerGroupContext
	 */
	public $ServerGroupContext;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ServerGroupContext extends MediasiteContext {

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class queryPlayersRequest extends RequestMessage {

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class QueryPlayersResult {

	/**
	 * @var (object)ArrayOfPlayerContext
	 */
	public $Players;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ArrayOfPlayerContext {

	/**
	 * @var array[0, unbounded] of (object)PlayerContext
	 */
	public $PlayerContext;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class PlayerContext extends MediasiteContext {

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class createPresentationRequest extends RequestMessage {

	/**
	 * @var (object)CreatePresentationDetails
	 */
	public $CreationDetails;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class CreatePresentationDetails {

	/**
	 * @var string
	 */
	public $Title;

	/**
	 * @var string
	 */
	public $Description;

	/**
	 * @var dateTime
	 */
	public $RecordDateTime;

	/**
	 * @var int
	 */
	public $Duration;

	/**
	 * @var boolean
	 */
	public $LiveBroadcast;

	/**
	 * @var boolean
	 */
	public $AutomaticUpload;

	/**
	 * @var boolean
	 */
	public $AutomaticViewableStatus;

	/**
	 * @var boolean
	 */
	public $AllowPollSubmissions;

	/**
	 * @var boolean
	 */
	public $AllowViewingPollResults;

	/**
	 * @var boolean
	 */
	public $UseQAForum;

	/**
	 * @var string
	 */
	public $FolderId;

	/**
	 * @var string
	 */
	public $PlayerId;

	/**
	 * @var string
	 */
	public $ServerGroupId;

	/**
	 * @var string
	 */
	public $CdnPublishingPoint;

	/**
	 * @var string
	 */
	public $MediaEncodeProfileId;

	/**
	 * @var string
	 */
	public $TimeZoneId;

	/**
	 * @var string
	 *     NOTE: DataStatus should follow the following restrictions
	 *     You can have one of the following value
	 *     Scheduled
	 *     OpenForRecord
	 *     Recording
	 *     Recorded
	 *     Uploaded
	 */
	public $DataStatus;

	/**
	 * @var (object)ArrayOfString
	 */
	public $PresenterIdList;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class CreatePresentationResult {

	/**
	 * @var (object)PresentationContext
	 */
	public $Presentation;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class PresentationContext extends MediasiteContext {

	/**
	 * @var string
	 *     NOTE: Status should follow the following restrictions
	 *     You can have one of the following value
	 *     Scheduled
	 *     OpenForRecord
	 *     Recording
	 *     Recorded
	 *     Uploaded
	 */
	public $Status;

	/**
	 * @var boolean
	 */
	public $IsLive;

	/**
	 * @var boolean
	 */
	public $IsOnDemand;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class queryPresentationsRequest extends RequestMessage {

	/**
	 * @var (object)ArrayOfString
	 */
	public $FolderIdList;

	/**
	 * @var (object)FolderFilter
	 */
	public $FolderFilter;

	/**
	 * @var (object)ArrayOfString
	 */
	public $PresentationIdList;

	/**
	 * @var (object)PresentationFilter
	 */
	public $PresentationFilter;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class FolderFilter {

	/**
	 * @var array[1, 1] of ResourcePermissionMask
	 */
	public $PermissionMask;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class PresentationFilter {

	/**
	 * @var string
	 */
	public $TitleRegEx;

	/**
	 * @var dateTime
	 */
	public $StartDate;

	/**
	 * @var dateTime
	 */
	public $EndDate;

	/**
	 * @var string
	 */
	public $PresenterIdentifier;

	/**
	 * @var string
	 */
	public $Owner;

	/**
	 * @var (object)ArrayOfPresentationDataStatus
	 */
	public $StatusFilterList;

	/**
	 * @var array[1, 1] of ResourcePermissionMask
	 */
	public $PermissionMask;

	/**
	 * @var array[1, 1] of FolderFilterMask
	 */
	public $FolderMask;

	/**
	 * @var string
	 */
	public $SearchText;

	/**
	 * @var array[1, 1] of PresentationSearchFields
	 */
	public $FieldsToSearch;

	/**
	 * @var string
	 *     NOTE: SearchType should follow the following restrictions
	 *     You can have one of the following value
	 *     None
	 *     AnyWord
	 *     AllWords
	 *     ExactPhrase
	 */
	public $SearchType;

	/**
	 * @var string
	 *     NOTE: SortBy should follow the following restrictions
	 *     You can have one of the following value
	 *     Date
	 *     Title
	 *     Presenter
	 *     Status
	 */
	public $SortBy;

	/**
	 * @var string
	 *     NOTE: SortDirection should follow the following restrictions
	 *     You can have one of the following value
	 *     Descending
	 *     Ascending
	 */
	public $SortDirection;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ArrayOfPresentationDataStatus {

	/**
	 * @var array[0, unbounded] of string
	 *     NOTE: PresentationDataStatus should follow the following restrictions
	 *     You can have one of the following value
	 *     Scheduled
	 *     OpenForRecord
	 *     Recording
	 *     Recorded
	 *     Uploaded
	 */
	public $PresentationDataStatus;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class QueryPresentationsResult {

	/**
	 * @var (object)ArrayOfPresentationContext
	 */
	public $Presentations;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ArrayOfPresentationContext {

	/**
	 * @var array[0, unbounded] of (object)PresentationContext
	 */
	public $PresentationContext;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class querySlidesRequest extends RequestMessage {

	/**
	 * @var string
	 */
	public $PresentationId;

	/**
	 * @var int
	 */
	public $StartIndex;

	/**
	 * @var int
	 */
	public $Count;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class QuerySlidesResult {

	/**
	 * @var (object)ArrayOfSlide
	 */
	public $Slides;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ArrayOfSlide {

	/**
	 * @var array[0, unbounded] of (object)Slide
	 */
	public $Slide;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class Slide {

	/**
	 * @var int
	 */
	public $Number;

	/**
	 * @var int
	 */
	public $Time;

	/**
	 * @var string
	 */
	public $Url;

	/**
	 * @var string
	 */
	public $Title;

	/**
	 * @var string
	 */
	public $Description;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class queryTimeZoneDetailsRequest extends RequestMessage {

	/**
	 * @var (object)ArrayOfString
	 */
	public $TimeZoneIdList;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class QueryTimeZoneDetailsResult {

	/**
	 * @var (object)ArrayOfTimeZoneDetails
	 */
	public $Details;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ArrayOfTimeZoneDetails {

	/**
	 * @var array[0, unbounded] of (object)TimeZoneDetails
	 */
	public $TimeZoneDetails;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class TimeZoneDetails {

	/**
	 * @var string
	 */
	public $Id;

	/**
	 * @var string
	 */
	public $Name;

	/**
	 * @var string
	 */
	public $RegistryKey;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class queryPresenterDetailsRequest extends RequestMessage {

	/**
	 * @var (object)ArrayOfString
	 */
	public $PresenterIdList;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class QueryPresenterDetailsResult {

	/**
	 * @var (object)ArrayOfPresenterDetails
	 */
	public $Details;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ArrayOfPresenterDetails {

	/**
	 * @var array[0, unbounded] of (object)PresenterDetails
	 */
	public $PresenterDetails;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class PresenterDetails {

	/**
	 * @var string
	 */
	public $Id;

	/**
	 * @var string
	 */
	public $Prefix;

	/**
	 * @var string
	 */
	public $FirstName;

	/**
	 * @var string
	 */
	public $MiddleName;

	/**
	 * @var string
	 */
	public $LastName;

	/**
	 * @var string
	 */
	public $Suffix;

	/**
	 * @var string
	 */
	public $ImageUrl;

	/**
	 * @var string
	 */
	public $Email;

	/**
	 * @var string
	 */
	public $BioUrl;

	/**
	 * @var string
	 */
	public $Owner;

	/**
	 * @var string
	 */
	public $AdditionalInfo;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class queryMediaEncodeProfileDetailsRequest extends RequestMessage {

	/**
	 * @var (object)ArrayOfString
	 */
	public $MediaEncodeProfileIdList;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class QueryMediaEncodeProfileDetailsResult {

	/**
	 * @var (object)ArrayOfMediaEncodeProfileDetails
	 */
	public $Details;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ArrayOfMediaEncodeProfileDetails {

	/**
	 * @var array[0, unbounded] of (object)MediaEncodeProfileDetails
	 */
	public $MediaEncodeProfileDetails;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class queryChapterPointsRequest extends RequestMessage {

	/**
	 * @var string
	 */
	public $PresentationId;

	/**
	 * @var int
	 */
	public $StartIndex;

	/**
	 * @var int
	 */
	public $Count;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class QueryChapterPointsResult {

	/**
	 * @var (object)ArrayOfChapterPoint
	 */
	public $ChapterPoints;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ArrayOfChapterPoint {

	/**
	 * @var array[0, unbounded] of (object)ChapterPoint
	 */
	public $ChapterPoint;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ChapterPoint {

	/**
	 * @var string
	 */
	public $PresentationId;

	/**
	 * @var int
	 */
	public $ChapterNumber;

	/**
	 * @var string
	 */
	public $Title;

	/**
	 * @var int
	 */
	public $Time;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class queryFolderDetailsRequest extends RequestMessage {

	/**
	 * @var (object)ArrayOfString
	 */
	public $FolderIdList;

	/**
	 * @var array[1, 1] of ResourcePermissionMask
	 */
	public $PermissionMask;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class QueryFolderDetailsResult {

	/**
	 * @var (object)ArrayOfFolderDetails
	 */
	public $Details;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ArrayOfFolderDetails {

	/**
	 * @var array[0, unbounded] of (object)FolderDetails
	 */
	public $FolderDetails;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class FolderDetails {

	/**
	 * @var string
	 */
	public $Id;

	/**
	 * @var string
	 */
	public $Name;

	/**
	 * @var string
	 */
	public $Description;

	/**
	 * @var string
	 */
	public $ParentId;

	/**
	 * @var boolean
	 */
	public $HasChildFolders;

	/**
	 * @var string
	 *     NOTE: Type should follow the following restrictions
	 *     You can have one of the following value
	 *     Folder
	 *     Root
	 *     RecycleBin
	 */
	public $Type;

	/**
	 * @var string
	 */
	public $FolderPath;

	/**
	 * @var string
	 */
	public $Owner;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class logoutRequest {

	/**
	 * @var string
	 */
	public $UserTicket;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class LogoutResult {

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class queryPresentationTemplatesRequest extends RequestMessage {

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class QueryPresentationTemplatesResult {

	/**
	 * @var (object)ArrayOfPresentationTemplateContext
	 */
	public $Templates;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ArrayOfPresentationTemplateContext {

	/**
	 * @var array[0, unbounded] of (object)PresentationTemplateContext
	 */
	public $PresentationTemplateContext;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class PresentationTemplateContext extends MediasiteContext {

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class queryPresentationTemplateDetailsRequest extends RequestMessage {

	/**
	 * @var (object)ArrayOfString
	 */
	public $PresentationTemplateIdList;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class QueryPresentationTemplateDetailsResult {

	/**
	 * @var (object)ArrayOfPresentationTemplateDetails
	 */
	public $Details;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ArrayOfPresentationTemplateDetails {

	/**
	 * @var array[0, unbounded] of (object)PresentationTemplateDetails
	 */
	public $PresentationTemplateDetails;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class PresentationTemplateDetails {

	/**
	 * @var string
	 */
	public $Id;

	/**
	 * @var string
	 */
	public $Name;

	/**
	 * @var boolean
	 */
	public $AllowPollSubmissions;

	/**
	 * @var boolean
	 */
	public $AllowViewingPollResults;

	/**
	 * @var boolean
	 */
	public $UseQAForum;

	/**
	 * @var boolean
	 */
	public $LiveBroadcast;

	/**
	 * @var boolean
	 */
	public $AutomaticUpload;

	/**
	 * @var boolean
	 */
	public $AutomaticViewableStatus;

	/**
	 * @var boolean
	 */
	public $AudioOnly;

	/**
	 * @var string
	 */
	public $TimeZoneId;

	/**
	 * @var string
	 */
	public $ServerGroupId;

	/**
	 * @var string
	 */
	public $MediaEncodeProfileId;

	/**
	 * @var string
	 */
	public $PlayerId;

	/**
	 * @var (object)ArrayOfPresenterContext
	 */
	public $Presenters;

	/**
	 * @var string
	 */
	public $Owner;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class createPresentationFromTemplateRequest extends RequestMessage {

	/**
	 * @var (object)CreatePresentationFromTemplateDetails
	 */
	public $CreationDetails;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class CreatePresentationFromTemplateDetails {

	/**
	 * @var string
	 */
	public $PresentationTemplateId;

	/**
	 * @var string
	 */
	public $Title;

	/**
	 * @var string
	 */
	public $Description;

	/**
	 * @var dateTime
	 */
	public $RecordDateTime;

	/**
	 * @var int
	 */
	public $Duration;

	/**
	 * @var string
	 */
	public $FolderId;

	/**
	 * @var string
	 */
	public $CdnPublishingPoint;

	/**
	 * @var string
	 *     NOTE: DataStatus should follow the following restrictions
	 *     You can have one of the following value
	 *     Scheduled
	 *     OpenForRecord
	 *     Recording
	 *     Recorded
	 *     Uploaded
	 */
	public $DataStatus;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class CreatePresentationFromTemplateResult {

	/**
	 * @var (object)PresentationContext
	 */
	public $Presentation;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class querySitePropertiesRequest extends RequestMessage {

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class QuerySitePropertiesResult {

	/**
	 * @var (object)SiteProperties
	 */
	public $Properties;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class SiteProperties {

	/**
	 * @var string
	 */
	public $SiteId;

	/**
	 * @var string
	 */
	public $Name;

	/**
	 * @var string
	 */
	public $Description;

	/**
	 * @var string
	 */
	public $Edition;

	/**
	 * @var string
	 */
	public $Version;

	/**
	 * @var string
	 */
	public $Owner;

	/**
	 * @var string
	 */
	public $OwnerContact;

	/**
	 * @var string
	 */
	public $OwnerEmail;

	/**
	 * @var string
	 */
	public $RootFolderId;

	/**
	 * @var string
	 */
	public $RecycleBinFolderId;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class queryContentServerDetailsRequest extends RequestMessage {

	/**
	 * @var string
	 *     NOTE: QueryBy should follow the following restrictions
	 *     You can have one of the following value
	 *     Presentation
	 *     ServerType
	 */
	public $QueryBy;

	/**
	 * @var string
	 */
	public $PresentationId;

	/**
	 * @var string
	 *     NOTE: ServerType should follow the following restrictions
	 *     You can have one of the following value
	 *     None
	 *     Unknown
	 *     OnDemandServer
	 *     BroadcastServer
	 *     SlideServer
	 *     PlayerGraphics
	 *     Presenters
	 *     Presentations
	 *     Application
	 *     Config
	 */
	public $ServerType;

	/**
	 * @var boolean
	 */
	public $IncludeStorageSettings;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class QueryContentServerDetailsResult {

	/**
	 * @var (object)ArrayOfContentServerDetails
	 */
	public $Details;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ArrayOfContentServerDetails {

	/**
	 * @var array[0, unbounded] of (object)ContentServerDetails
	 */
	public $ContentServerDetails;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ContentServerDetails {

	/**
	 * @var string
	 */
	public $Id;

	/**
	 * @var string
	 */
	public $Name;

	/**
	 * @var string
	 *     NOTE: ServerType should follow the following restrictions
	 *     You can have one of the following value
	 *     None
	 *     Unknown
	 *     OnDemandServer
	 *     BroadcastServer
	 *     SlideServer
	 *     PlayerGraphics
	 *     Presenters
	 *     Presentations
	 *     Application
	 *     Config
	 */
	public $ServerType;

	/**
	 * @var (object)ArrayOfContentServerEndpoint
	 */
	public $ServerConnections;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ArrayOfContentServerEndpoint {

	/**
	 * @var array[0, unbounded] of (object)ContentServerEndpoint
	 */
	public $ContentServerEndpoint;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ContentServerEndpoint {

	/**
	 * @var string
	 *     NOTE: EndpointType should follow the following restrictions
	 *     You can have one of the following value
	 *     Unknown
	 *     Distribution
	 *     Storage
	 *     Broadcast
	 *     WebService
	 *     Local
	 *     UnicastRollover
	 */
	public $EndpointType;

	/**
	 * @var string
	 */
	public $Url;

	/**
	 * @var string
	 */
	public $Username;

	/**
	 * @var string
	 */
	public $Password;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class queryResourcePermissionsRequest extends RequestMessage {

	/**
	 * @var (object)ArrayOfResourceObjectRequest
	 */
	public $ResourceList;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ArrayOfResourceObjectRequest {

	/**
	 * @var array[0, unbounded] of (object)ResourceObjectRequest
	 */
	public $ResourceObjectRequest;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ResourceObjectRequest {

	/**
	 * @var string
	 */
	public $Id;

	/**
	 * @var string
	 *     NOTE: Type should follow the following restrictions
	 *     You can have one of the following value
	 *     Presentation
	 *     MediaEncodeProfile
	 *     ServerGroup
	 *     Presenter
	 *     Player
	 *     PresentationTemplate
	 *     SystemOperation
	 *     PortalResource
	 *     Folder
	 */
	public $Type;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class QueryResourcePermissionsResult {

	/**
	 * @var (object)ArrayOfResourceObjectResponse
	 */
	public $ResourceList;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ArrayOfResourceObjectResponse {

	/**
	 * @var array[0, unbounded] of (object)ResourceObjectResponse
	 */
	public $ResourceObjectResponse;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ResourceObjectResponse {

	/**
	 * @var string
	 */
	public $Id;

	/**
	 * @var array[1, 1] of ResourcePermissionMask
	 */
	public $PermissionMask;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class queryResourcePermissionListRequest extends RequestMessage {

	/**
	 * @var (object)ResourceObjectRequest
	 */
	public $Resource;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class QueryResourcePermissionListResult {

	/**
	 * @var (object)ResourcePermission
	 */
	public $ResourcePermission;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ResourcePermission {

	/**
	 * @var (object)ResourceObjectRequest
	 */
	public $Resource;

	/**
	 * @var (object)ArrayOfResourcePermissionEntry
	 */
	public $PermissionList;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ArrayOfResourcePermissionEntry {

	/**
	 * @var array[0, unbounded] of (object)ResourcePermissionEntry
	 */
	public $ResourcePermissionEntry;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ResourcePermissionEntry {

	/**
	 * @var string
	 */
	public $RoleId;

	/**
	 * @var array[1, 1] of ResourcePermissionMask
	 */
	public $PermissionMask;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class queryFoldersWithPresentationsRequest extends RequestMessage {

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class QueryFoldersWithPresentationsResult {

	/**
	 * @var (object)ArrayOfFolderDetails
	 */
	public $Folders;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class querySubFolderDetailsRequest extends RequestMessage {

	/**
	 * @var (object)ArrayOfString
	 */
	public $ParentFolderIdList;

	/**
	 * @var boolean
	 */
	public $IncludeAllSubFolders;

	/**
	 * @var array[1, 1] of ResourcePermissionMask
	 */
	public $PermissionMask;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class QuerySubFolderDetailsResult {

	/**
	 * @var (object)ArrayOfFolderDetails
	 */
	public $Folders;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class queryAllPresentationsRequest extends RequestMessage {

	/**
	 * @var (object)PresentationFilter
	 */
	public $PresentationFilter;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class QueryAllPresentationsResult {

	/**
	 * @var (object)ArrayOfPresentationContext
	 */
	public $Presentations;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class createAuthTicketRequest extends RequestMessage {

	/**
	 * @var (object)CreateAuthTicketSettings
	 */
	public $TicketSettings;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class CreateAuthTicketSettings {

	/**
	 * @var string
	 */
	public $Username;

	/**
	 * @var string
	 */
	public $ResourceId;

	/**
	 * @var string
	 */
	public $ClientIpAddress;

	/**
	 * @var int
	 */
	public $MinutesToLive;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class CreateAuthTicketResult {

	/**
	 * @var string
	 */
	public $AuthTicketId;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class removeAuthTicketRequest extends RequestMessage {

	/**
	 * @var string
	 */
	public $AuthTicketId;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class RemoveAuthTicketResult {

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class queryAuthTicketPropertiesRequest extends RequestMessage {

	/**
	 * @var string
	 */
	public $AuthTicketId;

	/**
	 * @var boolean
	 */
	public $RenewTicket;

	/**
	 * @var int
	 */
	public $MinutesToLive;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class QueryAuthTicketPropertiesResult {

	/**
	 * @var (object)AuthTicketProperties
	 */
	public $Properties;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class AuthTicketProperties {

	/**
	 * @var string
	 */
	public $TicketId;

	/**
	 * @var string
	 */
	public $Username;

	/**
	 * @var string
	 */
	public $ResourceId;

	/**
	 * @var string
	 */
	public $Owner;

	/**
	 * @var string
	 */
	public $ClientIpAddress;

	/**
	 * @var dateTime
	 */
	public $CreationTime;

	/**
	 * @var dateTime
	 */
	public $ExpirationTime;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class createPresenterRequest extends RequestMessage {

	/**
	 * @var (object)CreatePresenterDetails
	 */
	public $PresenterDetails;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class CreatePresenterDetails {

	/**
	 * @var string
	 */
	public $Prefix;

	/**
	 * @var string
	 */
	public $FirstName;

	/**
	 * @var string
	 */
	public $MiddleName;

	/**
	 * @var string
	 */
	public $LastName;

	/**
	 * @var string
	 */
	public $Suffix;

	/**
	 * @var string
	 */
	public $Email;

	/**
	 * @var string
	 */
	public $BioUrl;

	/**
	 * @var string
	 */
	public $AdditionalInfo;

	// You need to set only one from the following two vars

	/**
	 * @var Plain Binary
	 */
	public $Image;

	/**
	 * @var base64Binary
	 */
	public $Image_encoded;


}

/**
 * @package EngineAPI\modules\mediaSite
 */
class CreatePresenterResult {

	/**
	 * @var string
	 */
	public $PresenterId;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class createFolderRequest extends RequestMessage {

	/**
	 * @var string
	 */
	public $ParentId;

	/**
	 * @var string
	 */
	public $Name;

	/**
	 * @var string
	 */
	public $Description;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class CreateFolderResult {

	/**
	 * @var string
	 */
	public $Id;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class queryCatalogSharesRequest extends RequestMessage {

	/**
	 * @var array[1, 1] of ResourcePermissionMask
	 */
	public $PermissionMask;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class QueryCatalogSharesResult {

	/**
	 * @var (object)ArrayOfCatalogShare
	 */
	public $Shares;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ArrayOfCatalogShare {

	/**
	 * @var array[0, unbounded] of (object)CatalogShare
	 */
	public $CatalogShare;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class CatalogShare {

	/**
	 * @var string
	 */
	public $Id;

	/**
	 * @var string
	 */
	public $Name;

	/**
	 * @var string
	 */
	public $Description;

	/**
	 * @var string
	 */
	public $CatalogUrl;

}

/**
 * @package EngineAPI\modules\mediaSite
 */
class ExternalAccessClient{
	var $proxy;
	var $ImpersonationUsername;
	var $Ticket;

	function ExternalAccessClient($serviceLocation, $ticket=null)
	{
		// define the WSDL to class map
		$edas_class_map = array(
			"loginRequest" => "loginRequest",
			"LoginResult" => "LoginResult",
			"getVersionRequest" => "getVersionRequest",
			"GetVersionResult" => "GetVersionResult",
			"testRequest" => "testRequest",
			"TestResult" => "TestResult",
			"RequestMessage" => "RequestMessage",
			"queryPresentationDetailsRequest" => "queryPresentationDetailsRequest",
			"ArrayOfString" => "ArrayOfString",
			"QueryPresentationDetailsFilter" => "QueryPresentationDetailsFilter",
			"QueryPresentationDetailsResult" => "QueryPresentationDetailsResult",
			"ArrayOfPresentationDetails" => "ArrayOfPresentationDetails",
			"PresentationDetails" => "PresentationDetails",
			"ArrayOfPresenterContext" => "ArrayOfPresenterContext",
			"MediasiteContext" => "MediasiteContext",
			"PresenterContext" => "PresenterContext",
			"MediaEncodeProfileDetails" => "MediaEncodeProfileDetails",
			"ArrayOfVideoEncodingDetails" => "ArrayOfVideoEncodingDetails",
			"VideoEncodingDetails" => "VideoEncodingDetails",
			"AudioEncodingDetails" => "AudioEncodingDetails",
			"ArrayOfAudioEncodingDetails" => "ArrayOfAudioEncodingDetails",
			"SlideCaptureDetails" => "SlideCaptureDetails",
			"StabilizationDetails" => "StabilizationDetails",
			"ArrayOfSupportingLink" => "ArrayOfSupportingLink",
			"SupportingLink" => "SupportingLink",
			"ArrayOfPresentationAttachment" => "ArrayOfPresentationAttachment",
			"PresentationAttachment" => "PresentationAttachment",
			"queryTimeZonesRequest" => "queryTimeZonesRequest",
			"QueryTimeZonesResult" => "QueryTimeZonesResult",
			"ArrayOfTimeZoneContext" => "ArrayOfTimeZoneContext",
			"TimeZoneContext" => "TimeZoneContext",
			"queryMediaEncodeProfilesRequest" => "queryMediaEncodeProfilesRequest",
			"QueryMediaEncodeProfilesResult" => "QueryMediaEncodeProfilesResult",
			"ArrayOfMediaEncodeProfileContext" => "ArrayOfMediaEncodeProfileContext",
			"MediaEncodeProfileContext" => "MediaEncodeProfileContext",
			"queryPresentersRequest" => "queryPresentersRequest",
			"QueryPresentersResult" => "QueryPresentersResult",
			"queryServerGroupsRequest" => "queryServerGroupsRequest",
			"QueryServerGroupsResult" => "QueryServerGroupsResult",
			"ArrayOfServerGroupContext" => "ArrayOfServerGroupContext",
			"ServerGroupContext" => "ServerGroupContext",
			"queryPlayersRequest" => "queryPlayersRequest",
			"QueryPlayersResult" => "QueryPlayersResult",
			"ArrayOfPlayerContext" => "ArrayOfPlayerContext",
			"PlayerContext" => "PlayerContext",
			"createPresentationRequest" => "createPresentationRequest",
			"CreatePresentationDetails" => "CreatePresentationDetails",
			"CreatePresentationResult" => "CreatePresentationResult",
			"PresentationContext" => "PresentationContext",
			"queryPresentationsRequest" => "queryPresentationsRequest",
			"FolderFilter" => "FolderFilter",
			"PresentationFilter" => "PresentationFilter",
			"ArrayOfPresentationDataStatus" => "ArrayOfPresentationDataStatus",
			"QueryPresentationsResult" => "QueryPresentationsResult",
			"ArrayOfPresentationContext" => "ArrayOfPresentationContext",
			"querySlidesRequest" => "querySlidesRequest",
			"QuerySlidesResult" => "QuerySlidesResult",
			"ArrayOfSlide" => "ArrayOfSlide",
			"Slide" => "Slide",
			"queryTimeZoneDetailsRequest" => "queryTimeZoneDetailsRequest",
			"QueryTimeZoneDetailsResult" => "QueryTimeZoneDetailsResult",
			"ArrayOfTimeZoneDetails" => "ArrayOfTimeZoneDetails",
			"TimeZoneDetails" => "TimeZoneDetails",
			"queryPresenterDetailsRequest" => "queryPresenterDetailsRequest",
			"QueryPresenterDetailsResult" => "QueryPresenterDetailsResult",
			"ArrayOfPresenterDetails" => "ArrayOfPresenterDetails",
			"PresenterDetails" => "PresenterDetails",
			"queryMediaEncodeProfileDetailsRequest" => "queryMediaEncodeProfileDetailsRequest",
			"QueryMediaEncodeProfileDetailsResult" => "QueryMediaEncodeProfileDetailsResult",
			"ArrayOfMediaEncodeProfileDetails" => "ArrayOfMediaEncodeProfileDetails",
			"queryChapterPointsRequest" => "queryChapterPointsRequest",
			"QueryChapterPointsResult" => "QueryChapterPointsResult",
			"ArrayOfChapterPoint" => "ArrayOfChapterPoint",
			"ChapterPoint" => "ChapterPoint",
			"queryFolderDetailsRequest" => "queryFolderDetailsRequest",
			"QueryFolderDetailsResult" => "QueryFolderDetailsResult",
			"ArrayOfFolderDetails" => "ArrayOfFolderDetails",
			"FolderDetails" => "FolderDetails",
			"logoutRequest" => "logoutRequest",
			"LogoutResult" => "LogoutResult",
			"queryPresentationTemplatesRequest" => "queryPresentationTemplatesRequest",
			"QueryPresentationTemplatesResult" => "QueryPresentationTemplatesResult",
			"ArrayOfPresentationTemplateContext" => "ArrayOfPresentationTemplateContext",
			"PresentationTemplateContext" => "PresentationTemplateContext",
			"queryPresentationTemplateDetailsRequest" => "queryPresentationTemplateDetailsRequest",
			"QueryPresentationTemplateDetailsResult" => "QueryPresentationTemplateDetailsResult",
			"ArrayOfPresentationTemplateDetails" => "ArrayOfPresentationTemplateDetails",
			"PresentationTemplateDetails" => "PresentationTemplateDetails",
			"createPresentationFromTemplateRequest" => "createPresentationFromTemplateRequest",
			"CreatePresentationFromTemplateDetails" => "CreatePresentationFromTemplateDetails",
			"CreatePresentationFromTemplateResult" => "CreatePresentationFromTemplateResult",
			"querySitePropertiesRequest" => "querySitePropertiesRequest",
			"QuerySitePropertiesResult" => "QuerySitePropertiesResult",
			"SiteProperties" => "SiteProperties",
			"queryContentServerDetailsRequest" => "queryContentServerDetailsRequest",
			"QueryContentServerDetailsResult" => "QueryContentServerDetailsResult",
			"ArrayOfContentServerDetails" => "ArrayOfContentServerDetails",
			"ContentServerDetails" => "ContentServerDetails",
			"ArrayOfContentServerEndpoint" => "ArrayOfContentServerEndpoint",
			"ContentServerEndpoint" => "ContentServerEndpoint",
			"queryResourcePermissionsRequest" => "queryResourcePermissionsRequest",
			"ArrayOfResourceObjectRequest" => "ArrayOfResourceObjectRequest",
			"ResourceObjectRequest" => "ResourceObjectRequest",
			"QueryResourcePermissionsResult" => "QueryResourcePermissionsResult",
			"ArrayOfResourceObjectResponse" => "ArrayOfResourceObjectResponse",
			"ResourceObjectResponse" => "ResourceObjectResponse",
			"queryResourcePermissionListRequest" => "queryResourcePermissionListRequest",
			"QueryResourcePermissionListResult" => "QueryResourcePermissionListResult",
			"ResourcePermission" => "ResourcePermission",
			"ArrayOfResourcePermissionEntry" => "ArrayOfResourcePermissionEntry",
			"ResourcePermissionEntry" => "ResourcePermissionEntry",
			"queryFoldersWithPresentationsRequest" => "queryFoldersWithPresentationsRequest",
			"QueryFoldersWithPresentationsResult" => "QueryFoldersWithPresentationsResult",
			"querySubFolderDetailsRequest" => "querySubFolderDetailsRequest",
			"QuerySubFolderDetailsResult" => "QuerySubFolderDetailsResult",
			"queryAllPresentationsRequest" => "queryAllPresentationsRequest",
			"QueryAllPresentationsResult" => "QueryAllPresentationsResult",
			"createAuthTicketRequest" => "createAuthTicketRequest",
			"CreateAuthTicketSettings" => "CreateAuthTicketSettings",
			"CreateAuthTicketResult" => "CreateAuthTicketResult",
			"removeAuthTicketRequest" => "removeAuthTicketRequest",
			"RemoveAuthTicketResult" => "RemoveAuthTicketResult",
			"queryAuthTicketPropertiesRequest" => "queryAuthTicketPropertiesRequest",
			"QueryAuthTicketPropertiesResult" => "QueryAuthTicketPropertiesResult",
			"AuthTicketProperties" => "AuthTicketProperties",
			"createPresenterRequest" => "createPresenterRequest",
			"CreatePresenterDetails" => "CreatePresenterDetails",
			"CreatePresenterResult" => "CreatePresenterResult",
			"createFolderRequest" => "createFolderRequest",
			"CreateFolderResult" => "CreateFolderResult",
			"queryCatalogSharesRequest" => "queryCatalogSharesRequest",
			"QueryCatalogSharesResult" => "QueryCatalogSharesResult",
			"ArrayOfCatalogShare" => "ArrayOfCatalogShare",
			"CatalogShare" => "CatalogShare");

		$this->proxy = new SoapClient($serviceLocation."?WSDL", array ("classmap" => $edas_class_map));
		$this->Ticket = $ticket;
	}

	function Version()
	{
		$request = new getVersionRequest();
		$response = $this->proxy->GetVersion($request);

		return $response->Version;
	}

	function Test()
	{
		$request = new testRequest();
		$response = $this->proxy->Test($request);

		return $response->Value;
	}

	function QuerySiteProperties()
	{
		$request = new querySitePropertiesRequest();
		$request->UserTicket = $this->Ticket;

		$response = $this->proxy->QuerySiteProperties($request);

		return $response->Properties;
	}


	function Login($username, $password)
	{
		$request = new loginRequest();
		$request->Username = $username;
		$request->Password = $password;

		$response = $this->proxy->Login($request);

		$this->Ticket = $response->UserTicket;
	}

	function LoginWithImpersonation($username, $password, $impersonationUsername)
	{
		$request = new loginRequest();
		$request->Username = $username;
		$request->Password = $password;
		$request->ImpersonationUsername	= $impersonationUsername;

		$response = $this->proxy->Login($request);

		$this->Ticket = $response->UserTicket;
	}

	function Logout($userTicket)
	{
		$request = new logoutRequest();
		$request->UserTicket = $userTicket;

		$response = $this->proxy->Logout($request);
	}


	function QueryPresentationDetails($presentationIdList, $filter=null)
	{
		$request = new queryPresentationDetailsRequest();
		$request->UserTicket = $this->Ticket;
		$request->ImpersonationUsername = $this->ImpersonationUsername;
		$request->PresentationIdList = $presentationIdList;
		$request->PresentationFilter = $filter;

		$response = $this->proxy->QueryPresentationDetails($request);

		return $response->Details->PresentationDetails;
	}

	function QueryTimeZones()
	{
		$request = new queryTimeZonesRequest();
		$request->UserTicket = $this->Ticket;
		$request->ImpersonationUsername = $this->ImpersonationUsername;

		$response = $this->proxy->QueryTimeZones($request);

		return $response->TimeZones->TimeZoneContext;
	}

	function QueryMediaEncodeProfiles()
	{
		$request = new queryMediaEncodeProfilesRequest();
		$request->UserTicket = $this->Ticket;
		$request->ImpersonationUsername = $this->ImpersonationUsername;

		$response = $this->proxy->QueryMediaEncodeProfiles($request);

		return $response->MediaEncodeProfiles->MediaEncodeProfileContext;
	}

	function QueryPresenters()
	{
		$request = new queryPresentersRequest();
		$request->UserTicket = $this->Ticket;
		$request->ImpersonationUsername = $this->ImpersonationUsername;

		$response = $this->proxy->QueryPresenters($request);

		return $response->Presenters->PresenterContext;
	}

	function QueryServerGroups()
	{
		$request = new queryServerGroupsRequest();
		$request->UserTicket = $this->Ticket;
		$request->ImpersonationUsername = $this->ImpersonationUsername;

		$response = $this->proxy->QueryServerGroups($request);

		return $response->ServerGroups->ServerGroupContext;
	}

	function QueryPlayers()
	{
		$request = new queryPlayersRequest();
		$request->UserTicket = $this->Ticket;
		$request->ImpersonationUsername = $this->ImpersonationUsername;

		$response = $this->proxy->QueryPlayers($request);

		return $response->Players->PlayerContext;
	}

	function CreatePresentation($creationDetails)
	{
		if(!isset($creationDetails->FolderId))
		{
			$creationDetails->FolderId = '';
		}

		$request = new createPresentationRequest();
		$request->UserTicket = $this->Ticket;
		$request->ImpersonationUsername = $this->ImpersonationUsername;
		$request->CreationDetails = $creationDetails;

		$response = $this->proxy->CreatePresentation($request);

		return $response->Presentation;
	}

	function QueryPresentations($folderIdList, $presentationFilter=null)
	{
		if($presentationFilter == null)
		{
			$presentationFilter = new PresentationFilter();
			$presentationFilter->PermissionMask = 'Read';
			$presentationFilter->FolderMask = 'Normal';
		}

		$request = new queryPresentationsRequest();
		$request->UserTicket = $this->Ticket;
		$request->ImpersonationUsername = $this->ImpersonationUsername;
		$request->FolderIdList = $folderIdList;
		$request->PresentationFilter = $presentationFilter;

		$response = $this->proxy->QueryPresentations($request);

		return $response->Presentations->PresentationContext;
	}

	function QuerySlides($presentationId, $startIndex, $count)
	{
		$request = new querySlidesRequest();
		$request->UserTicket = $this->Ticket;
		$request->ImpersonationUsername = $this->ImpersonationUsername;
		$request->PresentationId = $presentationId;
		$request->StartIndex = $startIndex;
		$request->Count = $count;

		$response = $this->proxy->QuerySlides($request);

		return $response->Slides->Slide;
	}

	function QueryTimeZoneDetails($timezoneIdList)
	{
		$request = new queryTimeZoneDetailsRequest();
		$request->UserTicket = $this->Ticket;
		$request->ImpersonationUsername = $this->ImpersonationUsername;
		$request->TimeZoneIdList = $timezoneIdList;

		$response = $this->proxy->QueryTimeZoneDetails($request);

		return $response->Details->TimeZoneDetails;
	}

	function QueryPresenterDetails($presenterIdList)
	{
		$request = new queryPresenterDetailsRequest();
		$request->UserTicket = $this->Ticket;
		$request->ImpersonationUsername = $this->ImpersonationUsername;
		$request->PresenterIdList = $presenterIdList;

		$response = $this->proxy->QueryPresenterDetails($request);

		return $response->Details->PresenterDetails;
	}

	function QueryMediaEncodeProfileDetails($profileIdList)
	{
		$request = new queryMediaEncodeProfileDetailsRequest();
		$request->UserTicket = $this->Ticket;
		$request->ImpersonationUsername = $this->ImpersonationUsername;
		$request->MediaEncodeProfileIdList = $profileIdList;

		$response = $this->proxy->QueryMediaEncodeProfileDetails($request);

		return $response->Details->MediaEncodeProfileDetails;
	}

	function QueryChapterPoints($presentationId, $startIndex, $count)
	{
		$request = new queryChapterPointsRequest();
		$request->UserTicket = $this->Ticket;
		$request->ImpersonationUsername = $this->ImpersonationUsername;
		$request->PresentationId = $presentationId;
		$request->StartIndex = $startIndex;
		$request->Count = $count;

		$response = $this->proxy->QueryChapterPoints($request);

		return $response->ChapterPoints->ChapterPoint;
	}

	function QueryFolderDetails($folderIdList, $permissionMask="Read")
	{
		$request = new queryFolderDetailsRequest();
		$request->UserTicket = $this->Ticket;
		$request->ImpersonationUsername = $this->ImpersonationUsername;
		$request->FolderIdList = $folderIdList;
		$request->PermissionMask = $permissionMask;

		$response = $this->proxy->QueryFolderDetails($request);

		return $response->Details->FolderDetails;
	}

	function QueryPresentationTemplates()
	{
		$request = new queryPresentationTemplatesRequest();
		$request->UserTicket = $this->Ticket;
		$request->ImpersonationUsername = $this->ImpersonationUsername;

		$response = $this->proxy->QueryPresentationTemplates($request);

		return $response->Templates->PresentationTemplateContext;
	}

	function QueryPresentationTemplateDetails($presentationTemplateIdList)
	{
		$request = new queryPresentationTemplateDetailsRequest();
		$request->UserTicket = $this->Ticket;
		$request->ImpersonationUsername = $this->ImpersonationUsername;
		$request->PresentationTemplateIdList = $presentationTemplateIdList;

		$response = $this->proxy->QueryPresentationTemplateDetails($request);

		return $response->Details->PresentationTemplateDetails;
	}

	function CreatePresentationFromTemplate($creationDetails)
	{
		if(!isset($creationDetails->FolderId))
		{
			$creationDetails->FolderId = '';
		}

		$request = new createPresentationFromTemplateRequest();
		$request->UserTicket = $this->Ticket;
		$request->ImpersonationUsername = $this->ImpersonationUsername;
		$request->CreationDetails = $creationDetails;

		$response = $this->proxy->CreatePresentationFromTemplate($request);

		return $response->Presentation;
	}

	function QueryContentServerDetailsByPresentation($presentationId, $includeStorageSettings)
	{
		$request = new queryContentServerDetailsRequest();
		$request->UserTicket = $this->Ticket;
		$request->ImpersonationUsername = $this->ImpersonationUsername;
		$request->QueryBy = "Presentation";
		$request->PresentationId = $presentationId;
		$request->IncludeStorageSettings = $includeStorageSettings;

		$response = $this->proxy->QueryContentServerDetails($request);

		return $response->Details->ContentServerDetails;
	}

	function QueryContentServerDetailsByServerType($serverType, $includeStorageSettings)
	{
		$request = new queryContentServerDetailsRequest();
		$request->UserTicket = $this->Ticket;
		$request->ImpersonationUsername = $this->ImpersonationUsername;
		$request->QueryBy = "ServerType";
		$request->ServerType = $serverType;
		$request->IncludeStorageSettings = $includeStorageSettings;

		$response = $this->proxy->QueryContentServerDetails($request);

		return $response->Details->ContentServerDetails;
	}

	function QueryResourcePermissions($resourceList, $impersonationUsername=null)
	{
		$request = new queryResourcePermissionsRequest();
		$request->UserTicket = $this->Ticket;

		if($impersonationUsername == null)
		{
			$request->ImpersonationUsername = $this->ImpersonationUsername;
		}
		else
		{
			$request->ImpersonationUsername = $impersonationUsername;
		}

		$request->ResourceList = $resourceList;

		$response = $this->proxy->QueryResourcePermissions($request);

		return $response->ResourceObjectResponse;
	}

	function QueryResourcePermissionList($id, $resourceType)
	{
		$resource = new ResourceObjectRequest();
		$resource->Id = $id;
		$resource->Type = $resourceType;

		$request = new queryResourcePermissionListRequest();
		$request->UserTicket = $this->Ticket;
		$request->ImpersonationUsername = $this->ImpersonationUsername;
		$request->Resource = $resource;

		$response = $this->proxy->QueryResourcePermissionList($request);

		return $response->ResourcePermission;
	}

	function QueryFoldersWithPresentation($impersonationUsername=null)
	{
		$request = new queryFoldersWithPresentationsRequest();
		$request->UserTicket = $this->Ticket;

		if($impersonationUsername == null)
		{
			$request->ImpersonationUsername = $this->ImpersonationUsername;
		}
		else
		{
			$request->ImpersonationUsername = $impersonationUsername;
		}

		$response = $this->proxy->QueryFoldersWithPresentations($request);

		return $response->Folders->FolderDetails;
	}

	function QuerySubFolderDetails($parentFolderIdList, $includeAllSubFolders, $permissionMask="Read")
	{
		$request = new querySubFolderDetailsRequest();
		$request->UserTicket = $this->Ticket;
		$request->ImpersonationUsername = $this->ImpersonationUsername;
		$request->ParentFolderIdList = $parentFolderIdList;
		$request->IncludeAllSubFolders = $includeAllSubFolders;
		$request->PermissionMask = $permissionMask;

		$response = $this->proxy->QuerySubFolderDetails($request);

		return $response->Folders->FolderDetails;
	}

	function QueryAllPresentations($presentationFilter)
	{
		$request = new queryAllPresentationsRequest();
		$request->UserTicket = $this->Ticket;
		$request->ImpersonationUsername = $this->ImpersonationUsername;
		$request->PresentationFilter = $presentationFilter;

		$response = $this->proxy->QueryAllPresentations($request);

		return $response->Presentations->PresentationContext;
	}

	function CreateAuthTicket($username, $resourceId, $clientIpAddress, $minutesToLive)
	{
		$settings = new CreateAuthTicketSettings();
		$settings->Username = $username;
		$settings->ResourceId = $resourceId;
		$settings->ClientIpAddress = $clientIpAddress;
		$settings->MinutesToLive = $minutesToLive;

		$request = new createAuthTicketRequest();
		$request->UserTicket = $this->Ticket;
		$request->ImpersonationUsername = $this->ImpersonationUsername;
		$request->TicketSettings = $settings;

		$response = $this->proxy->CreateAuthTicket($request);

		return $response->AuthTicketId;
	}

	function RemoveAuthTicket($authTicketId)
	{
		$request = new removeAuthTicketRequest();
		$request->UserTicket = $this->Ticket;
		$request->ImpersonationUsername = $this->ImpersonationUsername;
		$request->AuthTicketId = $authTicketId;

		$response = $this->proxy->RemoveAuthTicket($request);
	}

	function QueryAuthTicketProperties($authTicketId, $minutesToLive=null)
	{
		$request = new queryAuthTicketPropertiesRequest();
		$request->UserTicket = $this->Ticket;
		$request->ImpersonationUsername = $this->ImpersonationUsername;
		$request->AuthTicketId = $authTicketId;
		$request->RenewTicket = !($minutesToLive == null);
		$request->MinutesToLive = $minutesToLive;

		$response = $this->proxy->QueryAuthTicketProperties($request);

		return $response->Properties;
	}

	function CreatePresenter($createDetails)
	{
		$request = new createPresenterRequest();
		$request->UserTicket = $this->Ticket;
		$request->ImpersonationUsername = $this->ImpersonationUsername;
		$request->PresenterDetails = $createDetails;

		$response = $this->proxy->CreatePresenter($request);

		return $response->PresenterId;
	}

	function CreateFolder($parentId, $name, $description)
	{
		$request = new createFolderRequest();
		$request->UserTicket = $this->Ticket;
		$request->ImpersonationUsername = $this->ImpersonationUsername;
		$request->ParentId = $parentId;
		$request->Name = $name;
		$request->Description = $description;

		$response = $this->proxy->CreateFolder($request);

		return $response->Id;
	}

	function QueryCatalogShares($permissionMask)
	{
		$request = new queryCatalogSharesRequest();
		$request->UserTicket = $this->Ticket;
		$request->ImpersonationUsername = $this->ImpersonationUsername;
		$request->PermissionMask = $permissionMask;

		$response = $this->proxy->QueryCatalogShares($request);

		return $response->Shares->CatalogShare;
	}
}

/**
 * @package EngineAPI\modules\mediaSite
 */
class mediaSite {

	private $mediasiteServerRoot = NULL;
	private $mediasiteUsername   = NULL;
	private $mediasitePassword   = NULL;
	private $ticketLifetime      = NULL;
	private $serviceEndpoint     = NULL;
	private $mediasitePlayerRoot = NULL;
	private $mediasiteGroupRoot  = NULL;
	private $ticketUsername      = 'user@example.com';

	function __construct($attPairs=array()) {

		global $engineVars;

		$this->mediasiteServerRoot = isset($attPairs['url'])      ? $attPairs['url']      : $engineVars['mediaSite']['url'];
		$this->mediasiteUsername   = isset($attPairs['username']) ? $attPairs['username'] : $engineVars['mediaSite']['username'];
		$this->mediasitePassword   = isset($attPairs['password']) ? $attPairs['password'] : $engineVars['mediaSite']['password'];
		$this->ticketLifetime      = isset($attPairs['authLen'])  ? $attPairs['authLen']  : $engineVars['mediaSite']['authLen'];

		$this->serviceEndpoint     = $this->mediasiteServerRoot.'/ExternalDataAccess_5_0/Service.asmx';
		$this->mediasitePlayerRoot = $this->mediasiteServerRoot.'/Viewer';
		$this->mediasiteGroupRoot  = $this->mediasiteServerRoot.'/Catalog/pages/catalog.aspx?';

	}

	function __destruct() {
	}

	public function genURL($resourceId,$direct=TRUE) {

		// Create the web service client -  This requires the PHP5 SOAP extension to be loaded
		try
		{
			$client = new ExternalAccessClient($this->serviceEndpoint);
		}
		catch (Exception $e)
		{
			//echo "Edas Client Constructor Error: ";
			//echo $e->getMessage();
			return FALSE;
		}

		// Login to the Mediasite EDAS web service
		try
		{
			$client->Login($this->mediasiteUsername,$this->mediasitePassword);
		}
		catch (Exception $e)
		{
			//echo "Login Error: ";
			//echo $e->getMessage();
			return FALSE;
		}

		try
		{
			$authTicketId = $client->CreateAuthTicket($this->ticketUsername, $resourceId, null, $this->ticketLifetime);
		}
		catch (Exception $e)
		{
			//echo "Error creating auth ticket: ";
			//echo $e->getMessage();
			return FALSE;//$e->getMessage();
		}

		//Display the link to the Mediasite content using the auth ticket
		if ($direct === TRUE) {
			$playbackUrl = $this->mediasitePlayerRoot.'/?peid='.$resourceId.'&authTicket='.$authTicketId;
		}
		else {
			$playbackUrl = $this->mediasiteGroupRoot."catalogId=".$resourceId.'&authTicket='.$authTicketId;
		}
		return($playbackUrl);
	}

	public function getVideoInfo($resourceId) {
		// Create the web service client -  This requires the PHP5 SOAP extension to be loaded
		try
		{
			$client = new ExternalAccessClient($this->serviceEndpoint);
		}
		catch (Exception $e)
		{
			//echo "Edas Client Constructor Error: ";
			//echo $e->getMessage();
			return FALSE;
		}

		// Login to the Mediasite EDAS web service
		try
		{
			$client->Login($this->mediasiteUsername,$this->mediasitePassword);
		}
		catch (Exception $e)
		{
			// echo "Login Error: ";
			// echo $e->getMessage();
			return FALSE;
		}

		try
		{
			$vidObj = $client->QueryPresentationDetails($resourceId);
		}
		catch (Exception $e)
		{
			// echo "Error creating auth ticket: ";
			// echo $e->getMessage();
			return FALSE;//$e->getMessage();
		}

		return($vidObj);
	}
}

?>