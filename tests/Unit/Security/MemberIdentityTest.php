<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Security;

use App\Domain\Member\Api\Command\DomainApiMemberAuthCommandService;
use App\Domain\Member\Entity\MemberEntity;
use App\Domain\Member\Repository\MemberRepository;
use App\Interface\Api\Request\V1\LoginRequest;
use App\Interface\Common\CurrentMember;
use Hyperf\Context\RequestContext;
use Hyperf\Di\Container;
use Hyperf\Di\Definition\DefinitionSource;
use Hyperf\HttpMessage\Server\Request;
use Lcobucci\JWT\Token\DataSet;
use Lcobucci\JWT\UnencryptedToken;
use PHPUnit\Framework\TestCase;
use Plugin\Wechat\Interfaces\MiniAppInterface;

final class MemberIdentityTest extends TestCase
{
    public function testSharedServiceReadsEachRequestsIdentity(): void
    {
        $container = new Container(new DefinitionSource([]));
        foreach ([101, 202, null, 303] as $id) {
            $request = new Request('GET', '/');
            if ($id !== null) {
                $token = $this->createMock(UnencryptedToken::class);
                $token->method('claims')->willReturn(new DataSet(['jti' => 'member:' . $id], ''));
                $request = $request->withAttribute('token', $token);
            }
            RequestContext::set($request);
            self::assertSame($id ?? 0, $container->get(CurrentMember::class)->id());
        }
        \Hyperf\Context\Context::destroy(\Psr\Http\Message\ServerRequestInterface::class);
    }

    public function testCoreLoginUsesOnlyWechatIdentityWithoutDistributionPlugin(): void
    {
        $member = new MemberEntity();
        $member->setId(101);
        $repo = $this->createMock(MemberRepository::class);
        $repo->expects(self::once())->method('findByOpenid')->with('verified-openid')->willReturn($member);
        $repo->method('updateEntity')->willReturn(true);
        $wechat = $this->createMock(MiniAppInterface::class);
        $wechat->method('silentAuthorize')->willReturn(['openid' => 'verified-openid']);
        self::assertSame(101, (new DomainApiMemberAuthCommandService($repo, $wechat))->miniProgramLogin('verified-code')->getId());
        $request = (new \ReflectionClass(LoginRequest::class))->newInstanceWithoutConstructor();
        self::assertArrayNotHasKey('openid', $request->rules());
        self::assertNotContains('manualOpenid', array_map(static fn ($p) => $p->name, (new \ReflectionMethod(DomainApiMemberAuthCommandService::class, 'miniProgramLogin'))->getParameters()));
    }
}
